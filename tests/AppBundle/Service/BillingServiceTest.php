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
            ->with(['remoteDesktop' => $remoteDesktop], ['datetimeOccured' => 'DESC'], 1000)
            ->willReturn([]);

        $bs = new BillingService($remoteDesktopEventsRepo, new BillableItemsRepositoryInterface());

        $billableItems = $bs->generateBillableItems($remoteDesktop);

        $this->assertEmpty($billableItems);
    }

    public function testOneBillableItemForLaunchedRemoteDesktop()
    {
        $remoteDesktop = new RemoteDesktop();
        $remoteDesktop->setId('r1');

        $event = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_LAUNCHED,
            new \DateTime('2017-03-26 18:37:01', new \DateTimeZone('UTC'))
        );

        $remoteDesktopEventsRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $remoteDesktopEventsRepo->expects($this->once())
            ->method('findBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['datetimeOccured' => 'DESC'], 1000)
            ->willReturn([$event]);

        $bs = new BillingService($remoteDesktopEventsRepo);



        $billableItems = $bs->generateBillableItems($remoteDesktop);
    }
}
