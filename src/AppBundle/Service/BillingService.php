<?php

namespace AppBundle\Service;

use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEventsRepositoryInterface;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use Doctrine\ORM\EntityRepository;

class BillingService
{
    protected $remoteDesktopEventsRepository;

    public function __construct(EntityRepository $remoteDesktopEventsRepository)
    {
        $this->remoteDesktopEventsRepository = $remoteDesktopEventsRepository;
    }

    public function generateBillableItems(RemoteDesktop $remoteDesktop) : array
    {
        $this->remoteDesktopEventsRepository->findBy(['remoteDesktop' => $remoteDesktop]);
        return [];
    }
}
