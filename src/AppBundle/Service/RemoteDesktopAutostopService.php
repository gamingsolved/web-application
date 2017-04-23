<?php

namespace AppBundle\Service;

use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEvent;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Utility\DateTimeUtility;
use Doctrine\Common\Persistence\ObjectRepository;

class RemoteDesktopAutostopService
{
    public function getOptimalHourlyAutostopTimesForRemoteDesktop(RemoteDesktop $remoteDesktop, ObjectRepository $remoteDesktopEventRepository) : array
    {
        /** @var RemoteDesktopEvent $latestBecameAvailableEvent */
        $latestBecameAvailableEvents = $remoteDesktopEventRepository->findBy(
            [
                'remoteDesktop' => $remoteDesktop->getId(),
                'eventType' => RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER
            ],
            ['datetimeOccured' => 'DESC'],
            1
        );

        if (sizeof($latestBecameAvailableEvents) === 0) {
            return [];
        } else {
            $latestBecameAvailableEvent = $latestBecameAvailableEvents[0];
            $result = [];
            $startTime = $latestBecameAvailableEvent->getDatetimeOccured();

            // Find out where to start
            $found = false;
            $currentUsageHour = 0;
            $testTime = (clone $startTime);
            while (!$found) {
                if (DateTimeUtility::createDateTime('now') < $testTime->add(new \DateInterval('PT3540S'))) {
                    $found = true;
                } else {
                    $testTime->add(new \DateInterval('PT60S'));
                    $currentUsageHour++;
                }
            }

            for ($i = 0; $i < 8; $i++) {
                $result[$i] = (clone $startTime)
                    ->add(new \DateInterval('PT' . ($currentUsageHour + $i) . 'H'))
                    ->add(new \DateInterval('PT3540S')); // 3540 seconds = 59 minutes
            }
            return $result;
        }
    }
}
