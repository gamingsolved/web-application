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

            /** @var RemoteDesktopEvent $remoteDesktopEvent */
            foreach ($remoteDesktopEvents as $remoteDesktopEvent) {
                if ($remoteDesktopEvent->getDatetimeOccured() <= $upto) {
                    if ($remoteDesktopEvent->getEventType() === RemoteDesktopEvent::EVENT_TYPE_DESKTOP_FINISHED_LAUNCHING) {

                        // Is this launch already covered by a billable item?
                        $billableItem = $this->billableItemRepository->findItemForDesktopCoveringDateTime(
                            $remoteDesktop,
                            $remoteDesktopEvent->getDatetimeOccured()
                        );

                        // No, it's not
                        if ($billableItem === false) {
                            $billableItem = new BillableItem($remoteDesktopEvent->getDatetimeOccured(), [$remoteDesktopEvent]);
                            $generatedBillableItems[] = $billableItem;
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
