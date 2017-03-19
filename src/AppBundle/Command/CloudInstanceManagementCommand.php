<?php

namespace AppBundle\Command;

use AppBundle\Coordinator\CloudInstance\CloudInstanceCoordinator;
use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProvider;
use AppBundle\Utility\Cryptor;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CloudInstanceManagementCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:cloudinstancemanagement')
            ->setDescription('Manages the coordination and lifecyle of cloud instances')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');


        foreach (CloudInstanceProvider::PROVIDERS as $provider) {
            $output->writeln('Attempting to handle cloud instances for provider: ' . $provider['name']);

            /** @var CloudInstanceCoordinator $cloudInstanceCoordinatorClass */
            $cloudInstanceCoordinatorClass = $provider['cloudInstanceCoordinatorClass'];

            /** @var EntityRepository $cloudInstanceRepo */
            $cloudInstanceRepo = $em->getRepository($provider['cloudInstanceClass']);
            $cloudInstancesInUse = $cloudInstanceRepo->findBy(['status' => CloudInstance::STATUS_IN_USE]);

            /** @var CloudInstance $cloudInstance */
            foreach ($cloudInstancesInUse as $cloudInstance) {
                $output->writeln('Found cloud instance ' . $cloudInstance->getId());
                $output->writeln('Current run status: ' . CloudInstance::getRunstatusName($cloudInstance->getRunstatus()));
                $output->writeln('Flavor: ' . $cloudInstance->getFlavor()->getInternalName());
                $output->writeln('Image: ' . $cloudInstance->getImage()->getInternalName());
                $output->writeln('Region: ' . $cloudInstance->getRegion()->getInternalName());

                $output->writeln('Admin password (decrypted): ' . $cloudInstance->getAdminPassword());

                $cryptor = new Cryptor();
                $output->writeln(
                    'Admin password (decrypted): ' .
                    $cryptor->decryptString(
                        $cloudInstance->getAdminPassword(),
                        $this->getContainer()->getParameter('secret')
                    )
                );

                if ($cloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_SCHEDULED_FOR_LAUNCH) {
                    $output->writeln('Action: launching the cloud instance');
                    if ($cloudInstanceCoordinatorClass::launch($cloudInstance)) {
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
                    if ($cloudInstanceCoordinatorClass::hasFinishedLaunching($cloudInstance)) {
                        $adminPassword = null;
                        try {
                            $adminPassword = $cloudInstanceCoordinatorClass::tryRetrievingAdminPassword(
                                $cloudInstance,
                                $this->getContainer()->getParameter('secret')
                            );
                        } catch (\Exception $e) {
                            $output->writeln('Could not retrieve admin password');
                        }
                        if (!is_null($adminPassword)) {
                            // We assume that we only have one chance to get the password, thus we store it in any case
                            $cloudInstance->setAdminPassword($adminPassword);
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
