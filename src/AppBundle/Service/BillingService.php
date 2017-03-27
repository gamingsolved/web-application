<?php

namespace AppBundle\Service;

use AppBundle\Entity\Billing\BillableItem;
use AppBundle\Entity\Billing\BillableItemRepository;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEvent;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use Doctrine\ORM\EntityRepository;

class BillingService
{
    protected $remoteDesktopEventRepository;

    /** @var BillableItemRepository $billableItemRepository */
    protected $billableItemRepository;

    public function __construct(EntityRepository $remoteDesktopEventRepository, EntityRepository $billableItemRepository)
    {
        $this->remoteDesktopEventRepository = $remoteDesktopEventRepository;
        $this->billableItemRepository = $billableItemRepository;
    }

    protected function generateProlongations(array $remoteDesktopEvents, BillableItem &$newestBillableItem, array &$generatedBillableItems, \DateTime $upto)
    {
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
                if ($lastRemoteDesktopEventInTimeWindow->getEventType() === RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BEGAN_STOPPING) {
                    $beganStoppingFound = true;
                }
            }

            // If the last event in the time window of the newest billable item wasn't "began stopping",
            // then we must assume that the desktop is still running, and need to prolongue.

            if (!$beganStoppingFound) {
                $newBillableItem = new BillableItem(
                    $newestBillableItem->getTimewindowEnd(), []
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
     * @return array
     */
    public function generateMissingBillableItems(RemoteDesktop $remoteDesktop, \DateTime $upto) : array
    {
        $remoteDesktopEvents = $this->remoteDesktopEventRepository->findBy(
            ['remoteDesktop' => $remoteDesktop],
            ['datetimeOccured' => 'ASC']
        );

        $generatedBillableItems = null;

        // No events means there is nothing billable
        if (sizeof($remoteDesktopEvents) === 0) {
            $generatedBillableItems = [];
        } else {

            /* We first need to find out if the newest known billable items needs to be "prolonged"
             * (i.e., it needs to be seamlessly followed by a new item because during the last existing
             * item, the desktop has not been stopped and is therefore still running, like this:
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

            /** @var BillableItem $newestBillableItem */
            $newestBillableItem = $this->billableItemRepository->findOneBy(
                ['remoteDesktop' => $remoteDesktop],
                ['timewindowBegin' => 'DESC']
            );

            if (!is_null($newestBillableItem)) {
                $this->generateProlongations($remoteDesktopEvents, $newestBillableItem, $generatedBillableItems, $upto);
            }



            // Now we need to find out if there is a completely new item we need to create.
            // This is the case even if prolongations were created, but they then the desktop
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
            foreach ($remoteDesktopEvents as $remoteDesktopEvent) {
                if ($remoteDesktopEvent->getDatetimeOccured() < $upto) {

                    // Only consider events which occured after the newest billable item we have, if any
                    if (   (!is_null($newestBillableItem) && $remoteDesktopEvent->getDatetimeOccured() >= $newestBillableItem->getTimewindowEnd())
                        || (is_null($newestBillableItem))
                    ) {

                        if ($remoteDesktopEvent->getEventType() === RemoteDesktopEvent::EVENT_TYPE_DESKTOP_FINISHED_LAUNCHING) {

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
                                $newBillableItem = new BillableItem($remoteDesktopEvent->getDatetimeOccured(), []);
                                $generatedBillableItems[] = $newBillableItem;
                                $newestBillableItem = clone($newBillableItem);

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

                                $this->generateProlongations($remoteDesktopEvents, $newestBillableItem, $generatedBillableItems, $upto);
                            }

                        }

                    }
                }
            }

        }

        if (is_null($generatedBillableItems)) {
            throw new \Exception('Missing branch in decision logic: the $generatedBillableItems was never actively set.');
        }

        return $generatedBillableItems;

    }
}
