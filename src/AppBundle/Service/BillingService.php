<?php

namespace AppBundle\Service;

use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEventsRepositoryInterface;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;

class BillingService
{
    protected $remoteDesktopEventsRepository;

    public function __construct(RemoteDesktopEventsRepositoryInterface $remoteDesktopEventsRepository)
    {
        $this->remoteDesktopEventsRepository = $remoteDesktopEventsRepository;
    }

    public function generateBillableItems(RemoteDesktop $remoteDesktop) : array
    {
        return [];
    }
}