<?php

namespace Tests\AppBundle\Service;

use AppBundle\Entity\Billing\BillableItemsRepositoryInterface;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEvent;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEventsRepositoryInterface;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Service\BillingService;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;


class BillingServiceTest extends TestCase
{
    public function testNoBillableItemsForRemoteDesktopWithoutEvents()
    {
        $remoteDesktop = new RemoteDesktop();
        $remoteDesktop->setId('r1');

        $remoteDesktopEventsRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $remoteDesktopEventsRepo->expects($this->once())
            ->method('findBy')
            ->with(['remoteDesktop' => $remoteDesktop])
            ->willReturn([]);

        $bs = new BillingService($remoteDesktopEventsRepo);

        $billableItems = $bs->generateBillableItems($remoteDesktop);

        $this->assertEmpty($billableItems);
    }
}
