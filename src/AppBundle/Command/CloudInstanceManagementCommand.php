<?php

namespace AppBundle\Command;

use AppBundle\Coordinator\CloudInstance\AwsCloudInstanceCoordinator;
use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstance;
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

        /** @var EntityRepository $awsCloudInstanceRepo */
        $awsCloudInstanceRepo = $em->getRepository('AppBundle\Entity\CloudInstance\AwsCloudInstance');

        $awsCloudInstancesInUse = $awsCloudInstanceRepo->findBy(['status' => CloudInstance::STATUS_IN_USE]);

        $output->writeln('Attempting to handle cloud instances');

        /** @var AwsCloudInstance $awsCloudInstance */
        foreach ($awsCloudInstancesInUse as $awsCloudInstance)
        {
            $output->writeln('Found cloud instance ' . $awsCloudInstance->getId());
            $output->writeln('Current run status: ' . CloudInstance::getRunstatusName($awsCloudInstance->getRunstatus()));
            $output->writeln('Flavor: ' . $awsCloudInstance->getFlavor()->getInternalName());
            $output->writeln('Image: ' . $awsCloudInstance->getImage()->getInternalName());
            $output->writeln('Region: ' . $awsCloudInstance->getRegion()->getInternalName());

            if ($awsCloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_SCHEDULED_FOR_LAUNCH) {
                $output->writeln('Action: launching the cloud instance');
                if (AwsCloudInstanceCoordinator::launchCloudInstance($awsCloudInstance)) {
                    $output->writeln('Action result: success');
                    $awsCloudInstance->setRunstatus(CloudInstance::RUNSTATUS_LAUNCHING);
                    $em->persist($awsCloudInstance);
                    $em->flush();
                } else {
                    $output->writeln('Action result: failure');
                }
            }

            if ($awsCloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_LAUNCHING) {
                $output->writeln('Action: launching');
                if (AwsCloudInstanceCoordinator::launchCloudInstance($awsCloudInstance)) {
                    $output->writeln('Action result: success');
                    $awsCloudInstance->setRunstatus(CloudInstance::RUNSTATUS_LAUNCHING);
                    $em->persist($awsCloudInstance);
                    $em->flush();
                } else {
                    $output->writeln('Action result: failure');
                }
            }

            $output->writeln('');
        }
    }
}
