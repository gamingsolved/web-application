<?php

namespace AppBundle\Command;

use AppBundle\Coordinator\CloudInstance\AwsCloudInstanceCoordinator;
use AppBundle\Coordinator\CloudInstance\CloudInstanceCoordinator;
use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProvider;
use AppBundle\Utility\Cryptor;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CloudInstanceManagementCommand extends ContainerAwareCommand
{
    const CLOUD_INSTANCE_CLASSES = [AwsCloudInstance::class];

    protected function configure()
    {
        $this
            ->setName('app:cloudinstancemanagement')
            ->setDescription('Manages the coordination and lifecyle of cloud instances')
            ->addArgument(
                'awsApiKey',
                InputArgument::REQUIRED,
                'Key for the AWS api.'
            )
            ->addArgument(
                'awsApiSecret',
                InputArgument::REQUIRED,
                'Secret for the AWS api.'
            )
            ->addArgument(
                'awsKeypairPrivateKeyFile',
                InputArgument::REQUIRED,
                'Path to the file holding the private key of the AWS keypair.'
            )
        ;
    }

    protected function getCloudInstanceCoordinatorForCloudInstance(
        CloudInstance $cloudInstance, InputInterface $input, OutputInterface $output) : CloudInstanceCoordinator {
        if ($cloudInstance instanceof AwsCloudInstance) {
            return new AwsCloudInstanceCoordinator(
                [
                    'apiKey' => $input->getArgument('awsApiKey'),
                    'apiSecret' => $input->getArgument('awsApiSecret'),
                    'keypairPrivateKey' => file_get_contents($input->getArgument('awsKeypairPrivateKeyFile'))
                ],
                $cloudInstance->getRegion(),
                $output
            );
        } else {
            throw new \Exception('No cloud instance coordinator for cloud instances of class ' . get_class($cloudInstance));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        foreach (self::CLOUD_INSTANCE_CLASSES as $cloudInstanceClass) {
            $output->writeln('Attempting to handle cloud instances of class: ' . $cloudInstanceClass);

            /** @var EntityRepository $cloudInstanceRepo */
            $cloudInstanceRepo = $em->getRepository($cloudInstanceClass);
            $cloudInstancesInUse = $cloudInstanceRepo->findBy(['status' => CloudInstance::STATUS_IN_USE]);

            /** @var CloudInstance $cloudInstance */
            foreach ($cloudInstancesInUse as $cloudInstance) {

                // We need a coordinator per instance bc e.g. AWS uses different API endpoints per region
                $cloudInstanceCoordinator = $this->getCloudInstanceCoordinatorForCloudInstance(
                    $cloudInstance,
                    $input,
                    $output
                );

                $output->writeln('Found cloud instance ' . $cloudInstance->getId());
                $output->writeln('Current run status: ' . CloudInstance::getRunstatusName($cloudInstance->getRunstatus()));
                $output->writeln('Flavor: ' . $cloudInstance->getFlavor()->getInternalName());
                $output->writeln('Image: ' . $cloudInstance->getImage()->getInternalName());
                $output->writeln('Region: ' . $cloudInstance->getRegion()->getInternalName());

                $output->writeln('Admin password (encrypted): ' . $cloudInstance->getAdminPassword());

                if ($cloudInstance->getAdminPassword() !== '') {
                    $cryptor = new Cryptor();
                    $output->writeln(
                        'Admin password (decrypted): ' .
                        $cryptor->decryptString(
                            $cloudInstance->getAdminPassword(),
                            $this->getContainer()->getParameter('secret')
                        )
                    );
                }

                if ($cloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_SCHEDULED_FOR_LAUNCH) {
                    $output->writeln('Action: launching the cloud instance');
                    if ($cloudInstanceCoordinator->launch($cloudInstance)) {
                        $output->writeln('Action result: success');
                        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_LAUNCHING);
                        $em->persist($cloudInstance);
                        $em->flush();
                    } else {
                        $output->writeln('Action result: failure');
                    }
                }

                if ($cloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_LAUNCHING) {
                    $output->writeln('Action: probing if finished launching, acquiring info');
                    if ($cloudInstanceCoordinator->hasFinishedLaunching($cloudInstance)) {
                        $adminPassword = null;
                        try {
                            $adminPassword = $cloudInstanceCoordinator->tryRetrievingAdminPassword(
                                $cloudInstance,
                                $this->getContainer()->getParameter('secret')
                            );
                        } catch (\Exception $e) {
                            $output->writeln('Could not retrieve admin password');
                        }
                        if (!is_null($adminPassword)) {
                            // We assume that we only have one chance to get the password, thus we store it in any case
                            $cloudInstance->setAdminPassword($adminPassword);
                            $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_RUNNING);
                            $em->persist($cloudInstance);
                            $em->flush();
                        }
                    }
                }

                $output->writeln('');
            }
        }
    }
}
