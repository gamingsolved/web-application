<?php

namespace AppBundle\Command;

use AppBundle\Coordinator\CloudInstance\AwsCloudInstanceCoordinator;
use AppBundle\Coordinator\CloudInstance\CloudInstanceCoordinator;
use AppBundle\Entity\Billing\AccountMovement;
use AppBundle\Entity\Billing\AccountMovementRepository;
use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEvent;
use AppBundle\Utility\DateTimeUtility;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

date_default_timezone_set('UTC');
ini_set('memory_limit', '1024M');

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

        /** @var AccountMovementRepository $accountMovementRepository */
        $accountMovementRepository = $em->getRepository(AccountMovement::class);

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
                $output->writeln('Owner: ' . $cloudInstance->getRemoteDesktop()->getUser()->getUsername());
                $output->writeln('Current run status: ' . CloudInstance::getRunstatusName($cloudInstance->getRunstatus()));
                $output->writeln('Provider instance ID: ' . $cloudInstance->getProviderInstanceId());
                $output->writeln('Flavor: ' . $cloudInstance->getFlavor()->getInternalName());
                $output->writeln('Image: ' . $cloudInstance->getImage()->getInternalName());
                $output->writeln('Region: ' . $cloudInstance->getRegion()->getInternalName());
                $output->writeln('Public IP: ' . $cloudInstance->getPublicAddress());
                $output->writeln('Admin password: ' . $cloudInstance->getAdminPassword());


                // Running

                if ($cloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_RUNNING) {

                    $stoppedOrTerminatedWhileRunning = false;

                    $accountBalance = $accountMovementRepository
                        ->getAccountBalanceForUser(
                            $cloudInstance->getRemoteDesktop()->getUser()
                        );

                    // If a user had $1.99 of balance and started a desktop, their balance
                    // is now $0.00 - don't kill instances in this case!
                    // But if they have negative balance, there is no reason to keep
                    // the instances running.
                    if ($accountBalance < 0.0) {
                        $output->writeln('Action: Scheduling stop, because at ' . $accountBalance . ', owner balance is below $0.00!');
                        $cloudInstance->getRemoteDesktop()->scheduleForStop();
                    } else {
                        $output->writeln('Action: We think the instance is running, but we verify this, and check for auto stop');

                        // Is it stopped?
                        if ($cloudInstanceCoordinator->cloudInstanceIsStopped($cloudInstance)) {
                            $output->writeln('Action result: other than we thought the instance is stopped, we mark as stopped too');
                            $cloudInstance->setPublicAddress('');
                            $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_STOPPED);
                            $em->persist($cloudInstance);
                            $em->flush();
                            $stoppedOrTerminatedWhileRunning = true;
                        }

                        // Is it terminated?
                        if ($cloudInstanceCoordinator->cloudInstanceIsTerminated($cloudInstance)) {
                            $output->writeln('Action result: other than we thought the instance is terminated, we mark as terminated too');
                            $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_TERMINATED);
                            $em->persist($cloudInstance);
                            $em->flush();
                            $stoppedOrTerminatedWhileRunning = true;
                        }

                        if ($stoppedOrTerminatedWhileRunning) {
                            $output->writeln('Action: Also logging for the billing logic that the desktop became unavailable');
                            $remoteDesktop = $cloudInstance->getRemoteDesktop();
                            $remoteDesktopEvent = new RemoteDesktopEvent(
                                $remoteDesktop,
                                RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER,
                                DateTimeUtility::createDateTime('now')
                            );
                            $remoteDesktop->addRemoteDesktopEvent($remoteDesktopEvent);
                            $em->persist($remoteDesktop);
                            $em->flush();

                        } else {
                            // Is auto stop time reached?
                            $output->writeln('Action: Checking if auto stop time has been reached');
                            $output->writeln('It is now ' . DateTimeUtility::createDateTime()->format('Y-m-d H:i:s') . ' UTC, scheduled for stop is at ' . $cloudInstance->getScheduleForStopAt()->format('Y-m-d H:i:s') . ' UTC');
                            if (!is_null($cloudInstance->getScheduleForStopAt()) && ($cloudInstance->getScheduleForStopAt() <= DateTimeUtility::createDateTime())) {
                                $output->writeln('Action result: auto stop time reached!');
                                $output->writeln('Action: Scheduling for stop');
                                $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_SCHEDULED_FOR_STOP);
                                $em->persist($cloudInstance);
                                $em->flush();
                                $output->writeln('Action result: done');
                            }
                        }
                    }
                }


                // Launching and Starting

                if (   $cloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_LAUNCHING
                    || $cloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_STARTING) {
                    $output->writeln('Action: probing if launch or start is complete');
                    if ($cloudInstanceCoordinator->cloudInstanceIsRunning($cloudInstance)) {
                        $output->writeln('Action result: success');

                        $output->writeln('Action: Trying to get public address and Windows admin password');

                        $publicAddress = $cloudInstanceCoordinator->getPublicAddressOfRunningCloudInstance($cloudInstance);
                        $adminPassword = $cloudInstanceCoordinator->getAdminPasswordOfRunningCloudInstance($cloudInstance);

                        if (!is_null($publicAddress) && !is_null($adminPassword)) {
                            $cloudInstance->setPublicAddress($publicAddress);
                            $cloudInstance->setAdminPassword($adminPassword);
                            $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_RUNNING);
                            $em->persist($cloudInstance);
                            $em->flush();
                            $output->writeln('Action result: success');
                        } else {
                            $output->writeln('Action result: failure');
                        }
                    } else {
                        $output->writeln('Action result: failure');
                    }
                }


                // Launching

                if ($cloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_SCHEDULED_FOR_LAUNCH) {

                    $hourlyCosts = $cloudInstance->getHourlyCosts();
                    $accountBalance = $accountMovementRepository
                        ->getAccountBalanceForUser(
                            $cloudInstance->getRemoteDesktop()->getUser()
                        );

                    if ($hourlyCosts > $accountBalance) {
                        $output->writeln('Action: would launch the cloud instance, but owner has insufficient balance');
                        $output->writeln('Hourly costs would be ' . $hourlyCosts . ', balance is only ' . $accountBalance);
                    } else {
                        $output->writeln('Action: launching the cloud instance');

                        try {
                            $cloudInstanceCoordinator->triggerLaunchOfCloudInstance($cloudInstance);
                            $cloudInstanceCoordinator->updateCloudInstanceWithCoordinatorSpecificInfoAfterLaunchWasTriggered($cloudInstance);
                            $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_LAUNCHING);
                            $em->persist($cloudInstance);
                            $em->flush();
                            $output->writeln('Action result: success');
                        } catch (\Exception $e) {
                            $output->writeln('Action result: failure, exception output follows');
                            $output->writeln($e->getMessage());
                        }
                    }
                }


                // Stopping

                if ($cloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_SCHEDULED_FOR_STOP) {
                    $output->writeln('Action: asking the cloud instance to stop');
                    try {
                        $cloudInstanceCoordinator->triggerStopOfCloudInstance($cloudInstance);
                        $output->writeln('Action result: success');
                        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_STOPPING);
                        $em->persist($cloudInstance);
                        $em->flush();
                    } catch (\Exception $e) {
                        $output->writeln('Action result: failure, exception output follows');
                        $output->writeln($e->getMessage());
                    }
                }

                if ($cloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_STOPPING) {
                    $output->writeln('Action: probing if stop is complete, retrieve info');
                    if ($cloudInstanceCoordinator->cloudInstanceIsStopped($cloudInstance)) {
                        $cloudInstance->setPublicAddress('');
                        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_STOPPED);
                        $em->persist($cloudInstance);
                        $em->flush();
                        $output->writeln('Action result: success');
                    } else {
                        $output->writeln('Action result: failure');
                    }
                }


                // Starting

                if ($cloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_SCHEDULED_FOR_START) {

                    $hourlyCosts = $cloudInstance->getHourlyCosts();
                    $accountBalance = $accountMovementRepository
                        ->getAccountBalanceForUser(
                            $cloudInstance->getRemoteDesktop()->getUser()
                        );

                    if ($hourlyCosts > $accountBalance) {
                        $output->writeln('Action: would start the cloud instance, but owner has insufficient balance');
                        $output->writeln('Hourly costs would be ' . $hourlyCosts . ', balance is only ' . $accountBalance);
                    } else {
                        $output->writeln('Action: asking the cloud instance to start');
                        try {
                            $cloudInstanceCoordinator->triggerStartOfCloudInstance($cloudInstance);
                            $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_STARTING);
                            $em->persist($cloudInstance);
                            $em->flush();
                            $output->writeln('Action result: success');
                        } catch (\Exception $e) {
                            $output->writeln('Action result: failure, exception output follows');
                            $output->writeln($e->getMessage());
                        }
                    }
                }


                // Terminating

                if ($cloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_SCHEDULED_FOR_TERMINATION) {
                    $output->writeln('Action: asking the cloud instance to terminate');
                    try {
                        $cloudInstanceCoordinator->triggerTerminationOfCloudInstance($cloudInstance);
                        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_TERMINATING);
                        $em->persist($cloudInstance);
                        $em->flush();
                        $output->writeln('Action result: success');
                    } catch (\Exception $e) {
                        $output->writeln('Action result: failure, exception output follows');
                        $output->writeln($e->getMessage());
                    }
                }

                if ($cloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_TERMINATING) {
                    $output->writeln('Action: probing if termination is complete, retrieve info');
                    if ($cloudInstanceCoordinator->cloudInstanceIsTerminated($cloudInstance)) {
                        $cloudInstance->setPublicAddress('');
                        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_TERMINATED);
                        $em->persist($cloudInstance);
                        $em->flush();
                        $output->writeln('Action result: success');
                    } else {
                        $output->writeln('Action result: failure');
                    }
                }

                $output->writeln('');
            }
        }
        $output->writeln('All done, exiting.');
    }
}
