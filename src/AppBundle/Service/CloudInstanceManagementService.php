<?php

namespace AppBundle\Service;

use AppBundle\Coordinator\CloudInstance\CloudInstanceCoordinatorFactory;
use AppBundle\Entity\Billing\AccountMovement;
use AppBundle\Entity\Billing\AccountMovementRepository;
use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEvent;
use AppBundle\Utility\DateTimeUtility;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CloudInstanceManagementService
{
    /** @var EntityManager */
    protected $em;

    /** @var AccountMovementRepository */
    protected $accountMovementRepository;

    /** @var CloudInstanceCoordinatorFactory */
    protected $cloudInstanceCoordinatorFactory;

    public function __construct(EntityManager $em, CloudInstanceCoordinatorFactory $cloudInstanceCoordinatorFactory)
    {
        $this->em = $em;
        $this->accountMovementRepository = $this->em->getRepository(AccountMovement::class);
        $this->cloudInstanceCoordinatorFactory = $cloudInstanceCoordinatorFactory;
    }

    public function manageCloudInstance(
        CloudInstance $cloudInstance,
        InputInterface $input,
        OutputInterface $output)
    {
        // We need a coordinator per instance bc e.g. AWS uses different API endpoints per region
        $cloudInstanceCoordinator = $this->cloudInstanceCoordinatorFactory->getCloudInstanceCoordinatorForCloudInstance(
            $cloudInstance,
            $input->getArgument('awsApiKey'),
            $input->getArgument('awsApiSecret'),
            $input->getArgument('awsKeypairPrivateKeyFile'),
            $output
        );

        $output->writeln('Found cloud instance ' . $cloudInstance->getId());
        $output->writeln('Owner: ' . $cloudInstance->getRemoteDesktop()->getUser()->getUsername());
        $output->writeln('Belongs to desktop: "' . $cloudInstance->getRemoteDesktop()->getTitle() . '" (' . $cloudInstance->getRemoteDesktop()->getId() . ')');
        $output->writeln('Current run status: ' . $cloudInstance->getRunstatus() . ' (' . CloudInstance::getRunstatusName($cloudInstance->getRunstatus()) . ')');
        $output->writeln('Provider instance ID: ' . $cloudInstance->getProviderInstanceId());
        $output->writeln('Flavor: ' . $cloudInstance->getFlavor()->getInternalName());
        $output->writeln('Image: ' . $cloudInstance->getImage()->getInternalName());
        $output->writeln('Region: ' . $cloudInstance->getRegion()->getInternalName());
        $output->writeln('Public IP: ' . $cloudInstance->getPublicAddress());
        $output->writeln('Admin password (shortened): ' . substr($cloudInstance->getAdminPassword(), 0, 3));


        // Running

        if ($cloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_RUNNING) {

            $stoppedOrTerminatedWhileRunning = false;

            $accountBalance = $this->accountMovementRepository
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
                    $this->em->persist($cloudInstance);
                    $this->em->flush();
                    $stoppedOrTerminatedWhileRunning = true;
                }

                // Is it terminated?
                if ($cloudInstanceCoordinator->cloudInstanceIsTerminated($cloudInstance)) {
                    $output->writeln('Action result: other than we thought the instance is terminated, we mark as terminated too');
                    $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_TERMINATED);
                    $this->em->persist($cloudInstance);
                    $this->em->flush();
                    $stoppedOrTerminatedWhileRunning = true;
                }

                if ($stoppedOrTerminatedWhileRunning) {

                    $output->writeln('Action: Also logging for the usage billing logic that the desktop became unavailable');
                    $remoteDesktop = $cloudInstance->getRemoteDesktop();
                    $remoteDesktopEvent = new RemoteDesktopEvent(
                        $remoteDesktop,
                        RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER,
                        DateTimeUtility::createDateTime('now')
                    );
                    $remoteDesktop->addRemoteDesktopEvent($remoteDesktopEvent);

                    if ($cloudInstance->getRunstatus() == CloudInstance::RUNSTATUS_TERMINATED) {
                        $output->writeln('Action: Also logging for the provisioning billing logic that the desktop is no longer provisioned');
                        $remoteDesktop = $cloudInstance->getRemoteDesktop();
                        $remoteDesktopEvent = new RemoteDesktopEvent(
                            $remoteDesktop,
                            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_WAS_UNPROVISIONED_FOR_USER,
                            DateTimeUtility::createDateTime('now')
                        );
                        $remoteDesktop->addRemoteDesktopEvent($remoteDesktopEvent);
                    }

                    $this->em->persist($remoteDesktop);
                    $this->em->flush();

                } else {
                    // Is auto stop time reached?
                    $output->writeln('Action: Checking if auto stop time has been reached');
                    $output->writeln('It is now ' . DateTimeUtility::createDateTime()->format('Y-m-d H:i:s') . ' UTC, scheduled for stop is at ' . $cloudInstance->getScheduleForStopAt()->format('Y-m-d H:i:s') . ' UTC');
                    if (!is_null($cloudInstance->getScheduleForStopAt()) && ($cloudInstance->getScheduleForStopAt() <= DateTimeUtility::createDateTime())) {
                        $output->writeln('Action result: auto stop time reached!');
                        $output->writeln('Action: Scheduling for stop');
                        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_SCHEDULED_FOR_STOP);
                        $this->em->persist($cloudInstance);
                        $this->em->flush();
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
                    $this->em->persist($cloudInstance);
                    $this->em->flush();
                    $output->writeln('Action result: success');
                } else {
                    $output->writeln('Action result: failure');
                }
            } else {
                $output->writeln('Action result: failure');
            }
        }


        // Scheduled for Launch

        if ($cloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_SCHEDULED_FOR_LAUNCH) {

            $hourlyUsageCosts = $cloudInstance->getHourlyUsageCosts();
            $accountBalance =
                $this->accountMovementRepository->getAccountBalanceForUser(
                    $cloudInstance->getRemoteDesktop()->getUser()
                );

            if ($hourlyUsageCosts > $accountBalance) {
                $output->writeln('Action: would launch the cloud instance, but owner has insufficient balance');
                $output->writeln('Hourly costs would be ' . $hourlyUsageCosts . ', balance is only ' . $accountBalance);
            } else {
                $output->writeln('Action: launching the cloud instance');

                try {
                    $cloudInstanceCoordinator->triggerLaunchOfCloudInstance($cloudInstance);
                    $cloudInstanceCoordinator->updateCloudInstanceWithProviderSpecificInfoAfterLaunchWasTriggered($cloudInstance);
                    $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_LAUNCHING);
                    $this->em->persist($cloudInstance);
                    $this->em->flush();
                    $output->writeln('Action result: success');
                } catch (\Exception $e) {
                    $output->writeln('Action result: failure, exception output follows');
                    $output->writeln($e->getMessage());
                }
            }
        }


        // Scheduled for Stop

        if ($cloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_SCHEDULED_FOR_STOP) {
            $output->writeln('Action: asking the cloud instance to stop');
            try {
                $cloudInstanceCoordinator->triggerStopOfCloudInstance($cloudInstance);
                $output->writeln('Action result: success');
                $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_STOPPING);
                $this->em->persist($cloudInstance);
                $this->em->flush();
            } catch (\Exception $e) {
                $output->writeln('Action result: failure, exception output follows');
                $output->writeln($e->getMessage());
            }
        }

        // Stopping

        if ($cloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_STOPPING) {
            $output->writeln('Action: probing if stop is complete');
            if ($cloudInstanceCoordinator->cloudInstanceIsStopped($cloudInstance)) {
                $cloudInstance->setPublicAddress('');
                $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_STOPPED);
                $this->em->persist($cloudInstance);
                $this->em->flush();
                $output->writeln('Action result: success');
            } else {
                $output->writeln('Action result: failure');
            }
        }


        // Starting

        if ($cloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_SCHEDULED_FOR_START) {

            $hourlyUsageCosts = $cloudInstance->getHourlyUsageCosts();
            $accountBalance = $this->accountMovementRepository
                ->getAccountBalanceForUser(
                    $cloudInstance->getRemoteDesktop()->getUser()
                );

            if ($hourlyUsageCosts > $accountBalance) {
                $output->writeln('Action: would start the cloud instance, but owner has insufficient balance');
                $output->writeln('Hourly costs would be ' . $hourlyUsageCosts . ', balance is only ' . $accountBalance);
            } else {
                $output->writeln('Action: asking the cloud instance to start');
                try {
                    $cloudInstanceCoordinator->triggerStartOfCloudInstance($cloudInstance);
                    $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_STARTING);
                    $this->em->persist($cloudInstance);
                    $this->em->flush();
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
                $this->em->persist($cloudInstance);
                $this->em->flush();
                $output->writeln('Action result: success');
            } catch (\Exception $e) {
                $output->writeln('Action result: failure, exception output follows');
                $output->writeln($e->getMessage());
            }
        }

        if ($cloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_TERMINATING) {
            $output->writeln('Action: probing if termination is complete');
            if ($cloudInstanceCoordinator->cloudInstanceIsTerminated($cloudInstance)) {
                $cloudInstance->setPublicAddress('');
                $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_TERMINATED);
                $this->em->persist($cloudInstance);
                $this->em->flush();
                $output->writeln('Action result: success');
            } else {
                $output->writeln('Action result: failure');
            }
        }

        $output->writeln('');
    }
}
