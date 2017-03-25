<?php

namespace Tests\AppBundle\Service;

use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEvent;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEventsRepositoryInterface;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Service\BillingService;
use PHPUnit\Framework\TestCase;


class MockRemoteDesktopEventsRepository implements RemoteDesktopEventsRepositoryInterface
{
    protected $events = [];

    public function countForRemoteDesktop(RemoteDesktop $remoteDesktop) : int
    {
        return count($this->events[$remoteDesktop->getId()]);
    }

    public function add(RemoteDesktopEvent $remoteDesktopEvent) : bool
    {
        $this->events[$remoteDesktopEvent->getId()][] = $remoteDesktopEvent;
        return true;
    }
}


class BillingServiceTest extends TestCase
{
    public function testNoBillableItemsForRemoteDesktopWithoutEvents()
    {
        $remoteDesktop = new RemoteDesktop();
        $remoteDesktop->setId('r1');

        $e = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_LAUNCHED,
            new \DateTime('now', new \DateTimeZone('UTC'))
        );

        $eventsRepo = new MockRemoteDesktopEventsRepository();
        $eventsRepo->add($e);

        $bs = new BillingService($eventsRepo);

        $billableItems = $bs->generateBillableItems($remoteDesktop);

        $this->assertEmpty($billableItems);
    }
}