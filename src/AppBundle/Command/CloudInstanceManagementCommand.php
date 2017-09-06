<?php

namespace AppBundle\Command;

use AppBundle\Coordinator\CloudInstance\CloudInstanceCoordinatorFactory;
use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstance\PaperspaceCloudInstance;
use AppBundle\Service\CloudInstanceManagementService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

date_default_timezone_set('UTC');
ini_set('memory_limit', '2048M');

class CloudInstanceManagementCommand extends ContainerAwareCommand
{
    const CLOUD_INSTANCE_CLASSES = [AwsCloudInstance::class, PaperspaceCloudInstance::class];

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
            ->addArgument(
                'paperspaceApiKey',
                InputArgument::REQUIRED,
                'Key for the Paperspace api.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $cloudInstanceManagementService = new CloudInstanceManagementService($em, new CloudInstanceCoordinatorFactory());

        foreach (self::CLOUD_INSTANCE_CLASSES as $cloudInstanceClass) {
            $output->writeln('Attempting to handle cloud instances of class: ' . $cloudInstanceClass);

            /** @var EntityRepository $cloudInstanceRepo */
            $cloudInstanceRepo = $em->getRepository($cloudInstanceClass);
            $cloudInstancesInUse = $cloudInstanceRepo->findBy(['status' => CloudInstance::STATUS_IN_USE]);

            /** @var CloudInstance $cloudInstance */
            foreach ($cloudInstancesInUse as $cloudInstance) {
                try {
                    $cloudInstanceManagementService->manageCloudInstance($cloudInstance, $input, $output);
                } catch (\Exception $e) {
                    $output->writeln('Exception while handling cloud instance ' . $cloudInstance->getId() . ' ("' . $cloudInstance->getRemoteDesktop()->getTitle() . '") by ' . $cloudInstance->getRemoteDesktop()->getUser()->getUsername());
                    $output->writeln($e->getMessage());
                    $output->writeln('Nevertheless continueing with next instance');
                    $output->writeln('');
                }
            }
        }
        $output->writeln('All done, exiting.');
    }
}
