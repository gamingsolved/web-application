<?php

namespace AppBundle\Service;

use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEventsRepositoryInterface;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use Doctrine\ORM\EntityRepository;

class BillingService
{
    protected $remoteDesktopEventRepository;
    protected $billableItemRepository;

    public function __construct(EntityRepository $remoteDesktopEventRepository, EntityRepository $billableItemRepository)
    {
        $this->remoteDesktopEventRepository = $remoteDesktopEventRepository;
        $this->billableItemRepository = $billableItemRepository;
    }

    public function generateBillableItems(RemoteDesktop $remoteDesktop) : array
    {
        $remoteDesktopEvents = $this->remoteDesktopEventRepository->findBy(
            ['remoteDesktop' => $remoteDesktop],
            ['datetimeOccured' => 'DESC'],
            1000
        );

        // No events means there is nothing billable
        if (sizeof($remoteDesktopEvents) === 0)
        {
            return [];
        }
    }
}
