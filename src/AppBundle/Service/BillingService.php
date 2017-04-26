<?php

namespace AppBundle\Service;

use AppBundle\Entity\Billing\BillableItem;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEvent;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use Doctrine\ORM\EntityRepository;

class BillingService
{
    protected $remoteDesktopEventRepository;

    /** @var EntityRepository $billableItemRepository */
    protected $billableItemRepository;

    public function __construct(EntityRepository $remoteDesktopEventRepository, EntityRepository $billableItemRepository)
    {
        $this->remoteDesktopEventRepository = $remoteDesktopEventRepository;
        $this->billableItemRepository = $billableItemRepository;
    }

    protected function getStartEventTypeFromBillableItemType(int $billableItemType) : int {
        if ($billableItemType === BillableItem::TYPE_USAGE) {
            return RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER;
        }
        if ($billableItemType === BillableItem::TYPE_PROVISIONING) {
            return RemoteDesktopEvent::EVENT_TYPE_DESKTOP_WAS_PROVISIONED_FOR_USER;
        }
        throw new \Exception('Cannot map billable item type ' . $billableItemType . ' to start event type.');
    }

    protected function getEndEventTypeFromBillableItemType(int $billableItemType) : int {
        if ($billableItemType === BillableItem::TYPE_USAGE) {
            return RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER;
        }
        if ($billableItemType === BillableItem::TYPE_PROVISIONING) {
            return RemoteDesktopEvent::EVENT_TYPE_DESKTOP_WAS_UNPROVISIONED_FOR_USER;
        }
        throw new \Exception('Cannot map billable item type ' . $billableItemType . ' to end event type.');
    }

    protected function generateProlongations(
        RemoteDesktop $remoteDesktop,
        array $remoteDesktopEvents,
        BillableItem &$newestBillableItem,
        array &$generatedBillableItems,
        \DateTime $upto,
        int $billableItemType
    )
    {
        $endEventType = $this->getEndEventTypeFromBillableItemType($billableItemType);

        $beganStoppingFound = false;
        $uptoReached = false;

        // We do not look into the future, that is, we do not try to prolong into billable items
        // whose start time would lie after $upto.
        if ($newestBillableItem->getTimewindowEnd() >= $upto) {
            return;
        }

        while ($beganStoppingFound === false && (!$uptoReached)) {

            $remoteDesktopEventsInTimewindow = [];

            /** @var RemoteDesktopEvent $remoteDesktopEvent */
            foreach ($remoteDesktopEvents as $remoteDesktopEvent) {
                if (   $remoteDesktopEvent->getDatetimeOccured() >= $newestBillableItem->getTimewindowBegin()
                    && $remoteDesktopEvent->getDatetimeOccured() < $newestBillableItem->getTimewindowEnd()
                    && $remoteDesktopEvent->getDatetimeOccured() < $upto
                ) {
                    $remoteDesktopEventsInTimewindow[] = $remoteDesktopEvent;
                }
            }

            if (sizeof($remoteDesktopEventsInTimewindow) !== 0) {
                /** @var RemoteDesktopEvent $lastRemoteDesktopEventInTimeWindow */
                $lastRemoteDesktopEventInTimeWindow = $remoteDesktopEventsInTimewindow[sizeof($remoteDesktopEventsInTimewindow) - 1];
                if ($lastRemoteDesktopEventInTimeWindow->getEventType() === $endEventType) {
                    $beganStoppingFound = true;
                }
            }

            // If the last event in the time window of the newest billable item wasn't "began stopping",
            // then we must assume that the desktop is still running or the instance still provisioned,
            // and we need to prolongue the billing.

            if (!$beganStoppingFound) {
                $newBillableItem = new BillableItem(
                    $remoteDesktop,
                    $newestBillableItem->getTimewindowEnd(),
                    $billableItemType
                );
                $generatedBillableItems[] = clone($newBillableItem);
                $newestBillableItem = $newBillableItem;
            }

            if ($newestBillableItem->getTimewindowEnd() >= $upto) {
                $uptoReached = true;
            }
        }
    }

    /**
     * @param RemoteDesktop $remoteDesktop
     * @param \DateTime $upto Point in time up to which to consider events - exclusive!
     * @param int $billableItemType What type of costs do we want to bill, usage or provisioning
     * @return array
     */
    public function generateMissingBillableItems(RemoteDesktop $remoteDesktop, \DateTime $upto, int $billableItemType) : array
    {
        $startEventType = $this->getStartEventTypeFromBillableItemType($billableItemType);
        $endEventType = $this->getEndEventTypeFromBillableItemType($billableItemType);

        $remoteDesktopEvents = $this->remoteDesktopEventRepository->findBy(
            ['remoteDesktop' => $remoteDesktop],
            ['datetimeOccured' => 'ASC']
        );

        $filteredRemoteDesktopEvents = [];

        /** @var RemoteDesktopEvent $remoteDesktopEvent */
        foreach ($remoteDesktopEvents as $remoteDesktopEvent) {
            if (   $remoteDesktopEvent->getEventType() === $startEventType
                || $remoteDesktopEvent->getEventType() === $endEventType)
            {
                $filteredRemoteDesktopEvents[] = $remoteDesktopEvent;
            }
        }

        $generatedBillableItems = [];

        // No events means there is nothing billable
        if (sizeof($filteredRemoteDesktopEvents) !== 0) {

            /* We first need to find out if the newest known billable item needs to be "prolonged"
             * (i.e., it needs to be seamlessly followed by a new item because during the last existing
             * item, the desktop has not been stopped (for usage billing) and is therefore still running, like this:
             *
             * desktop                     desktop     desktop
             * started                     stopped     started          up to
             * |                             |            |               |
             * |#########|**********|**********|--------------------------------->
             *     |          |           |
             *  item        this        this
             *  exists    needs to    needs to
             *           be created  be created
             */

            // Get the chronologically last billable item

            /** @var BillableItem $latestExistingBillableItem */
            $latestExistingBillableItem = $this->billableItemRepository->findOneBy(
                [
                    'remoteDesktop' => $remoteDesktop,
                    'type' => $billableItemType
                ],
                ['timewindowBegin' => 'DESC']
            );

            if (!is_null($latestExistingBillableItem)) {
                $this->generateProlongations(
                    $remoteDesktop,
                    $filteredRemoteDesktopEvents,
                    $latestExistingBillableItem,
                    $generatedBillableItems,
                    $upto,
                    $billableItemType
                );
            }


            // Now we need to find out if there is a completely new item we need to create.
            // This is the case even if prolongations were created, but then the desktop
            // was off for a longer period.

            /*
             * desktop                     desktop     desktop
             * started                     stopped     started          up to
             * |                             |            |               |
             * |#########|**********|**********|----------|#########------------->
             *     |          |           |                    |
             *  item       created by   created by          this needs
             *  exists     prolongation  prolongation      to be created
             */


            /** @var RemoteDesktopEvent $remoteDesktopEvent */
            foreach ($filteredRemoteDesktopEvents as $remoteDesktopEvent) {
                if ($remoteDesktopEvent->getDatetimeOccured() < $upto) {

                    // Only consider events which occured after the newest billable item we have, if any
                    if (   (!is_null($latestExistingBillableItem) && $remoteDesktopEvent->getDatetimeOccured() >= $latestExistingBillableItem->getTimewindowEnd())
                        || (is_null($latestExistingBillableItem))
                    ) {

                        if ($remoteDesktopEvent->getEventType() === $startEventType) {

                            // Is this launch already covered by a billable item?

                            /** @var BillableItem $generatedBillableItem */
                            $found = false;
                            if (!is_null($generatedBillableItems)) {
                                foreach ($generatedBillableItems as $generatedBillableItem) {
                                    if ($generatedBillableItem->getTimewindowBegin() >= $remoteDesktopEvent->getDatetimeOccured()
                                        && $generatedBillableItem->getTimewindowEnd() < $remoteDesktopEvent->getDatetimeOccured()
                                    ) {
                                        $found = true;
                                    }
                                }
                            }

                            if (!$found) {
                                $newBillableItem = new BillableItem(
                                    $remoteDesktop,
                                    $remoteDesktopEvent->getDatetimeOccured(),
                                    $billableItemType
                                );
                                $generatedBillableItems[] = $newBillableItem;
                                $latestExistingBillableItem = clone($newBillableItem);

                                // Now that we generated a new billable item, we immediately want to know if it has
                                // prolongations, to avoid generating further items which are not neccessary because
                                // the prolongations already cover that.

                                /*
                                 * desktop                     desktop     desktop
                                 * started                     stopped     started          up to
                                 * |                             |            |               |
                                 * |#########|**********|**********|----------|#########|#########|---->
                                 *     |          |           |                             |
                                 *  item       created by   created by                  this needs
                                 *  exists     prolongation  prolongation              to be created
                                 */

                                $this->generateProlongations(
                                    $remoteDesktop,
                                    $filteredRemoteDesktopEvents,
                                    $latestExistingBillableItem,
                                    $generatedBillableItems,
                                    $upto,
                                    $billableItemType
                                );
                            }

                        }

                    }
                }
            }

        }

        return $generatedBillableItems;

    }
}
