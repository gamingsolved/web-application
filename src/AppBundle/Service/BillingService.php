<?php

namespace AppBundle\Service;

use AppBundle\Entity\Billing\BillableItem;
use AppBundle\Entity\Billing\BillableItemRepository;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEvent;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEventsRepositoryInterface;
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

    /**
     * @param RemoteDesktop $remoteDesktop
     * @param \DateTime $upto Point in time up to which to consider events - inclusive!
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

            /* We first need to find out if there are billable items which need to be "prolonged"
             * (i.e., they need to be seamlessly followed by a new item because during the last existing
             * item, the desktop has not been stopped and is therefore still running, like this:
             *
             * desktop                     desktop
             * started                     stopped
             * |                             |
             * ##########|**********|**********|--------------------------------->
             *     |          |           |
             *  item        this        this
             *  exists    needs to    needs to
             *           be created  be created
             */

            // Get the chronologically last billable item

            /** @var BillableItem $newestExistingBillableItem */
            $newestExistingBillableItem = $this->billableItemRepository->findOneBy(
                ['remoteDesktop' => $remoteDesktop],
                ['timewindowBegin' => 'DESC']
            );

            if (!is_null($newestExistingBillableItem)) {

                $beganStoppingFound = false;

                while ($beganStoppingFound === false) {
                    /** @var RemoteDesktopEvent $remoteDesktopEvent */
                    foreach ($remoteDesktopEvents as $remoteDesktopEvent) {
                        if ($remoteDesktopEvent->getEventType() === RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BEGAN_STOPPING
                            && $remoteDesktopEvent->getDatetimeOccured() >= $newestExistingBillableItem->getTimewindowBegin()
                            && $remoteDesktopEvent->getDatetimeOccured() < $newestExistingBillableItem->getTimewindowEnd()
                        ) {
                            $beganStoppingFound = true;
                        }
                    }

                    // If the desktop did not begin stopping during this item's time window,
                    // then we must assume that is is still running

                    if (!$beganStoppingFound) {
                        $newExistingBillableItem = new BillableItem(
                            $newestExistingBillableItem->getTimewindowEnd(), []
                        );
                        $generatedBillableItems[] = clone($newExistingBillableItem);
                        $newestExistingBillableItem = $newExistingBillableItem;
                    }
                }

            }

            /** @var RemoteDesktopEvent $remoteDesktopEvent */
            foreach ($remoteDesktopEvents as $remoteDesktopEvent) {
                if ($remoteDesktopEvent->getDatetimeOccured() <= $upto) {
                    if ($remoteDesktopEvent->getEventType() === RemoteDesktopEvent::EVENT_TYPE_DESKTOP_FINISHED_LAUNCHING) {

                        // Is this launch already covered by a billable item?

                        $billableItem = $this->billableItemRepository->findItemForDesktopCoveringDateTime(
                            $remoteDesktop,
                            $remoteDesktopEvent->getDatetimeOccured()
                        );

                        // Not by a stored one
                        if ($billableItem === false) {

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

                            // And not by a generated one
                            if (!$found) {
                                $billableItem = new BillableItem($remoteDesktopEvent->getDatetimeOccured(), [$remoteDesktopEvent]);
                                $generatedBillableItems[] = $billableItem;
                            }
                        }
                    }
                }
            }


            // Do we need to create additional billable items because the desktop has not been stopped within the time
            // windows of existing billable items?



        }

        if (is_null($generatedBillableItems)) {
            throw new \Exception('Missing branch in decision logic: the $generatedBillableItems was never actively set.');
        }

        return $generatedBillableItems;

    }
}
