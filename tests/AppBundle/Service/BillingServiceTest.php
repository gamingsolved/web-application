<?php

namespace Tests\AppBundle\Service;

use AppBundle\Entity\RemoteDesktop\Events\RemoteDesktopEventsRepositoryInterface;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use PHPUnit\Framework\TestCase;


class MockRemoteDesktopEventsRepository implements RemoteDesktopEventsRepositoryInterface
{
    public function hasEvents(RemoteDesktop $remoteDesktop) : bool
    {
        return false;
    }
}


class BillingServiceTest extends TestCase
{
    public function testNoBillableItemsForRemoteDesktopWithoutEvents()
    {
        $remoteDesktop = new RemoteDesktop();
        $remoteDesktop->setId('r1');

    }
}