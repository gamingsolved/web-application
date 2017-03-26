<?php

namespace Tests\AppBundle\Service;

use AppBundle\Entity\Billing\BillableItemRepository;
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

        $remoteDesktopEventRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $remoteDesktopEventRepo->expects($this->once())
            ->method('findBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['datetimeOccured' => 'ASC'])
            ->willReturn([]);

        $billableItemRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $bs = new BillingService($remoteDesktopEventRepo, $billableItemRepo);

        $billableItems = $bs->generateMissingBillableItems($remoteDesktop, new \DateTime('now', new \DateTimeZone('UTC')));

        $this->assertEmpty($billableItems);
    }

    public function testOneBillableItemForLaunchedRemoteDesktop()
    {
        $remoteDesktop = new RemoteDesktop();
        $remoteDesktop->setId('r1');

        $event = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_FINISHED_LAUNCHING,
            new \DateTime('2017-03-26 18:37:01', new \DateTimeZone('UTC'))
        );

        $remoteDesktopEventRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $remoteDesktopEventRepo->expects($this->once())
            ->method('findBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['datetimeOccured' => 'ASC'])
            ->willReturn([$event]);

        $billableItemRepo = $this
            ->getMockBuilder(BillableItemRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $billableItemRepo->expects($this->once())
            ->method('findItemForDesktopCoveringDateTime')
            ->with($remoteDesktop, $event->getDatetimeOccured())
            ->willReturn(false);

        $bs = new BillingService($remoteDesktopEventRepo, $billableItemRepo);

        $billableItems = $bs->generateMissingBillableItems($remoteDesktop, new \DateTime('2017-03-26 18:40:00', new \DateTimeZone('UTC')));

        $this->assertCount(1, $billableItems);

        /** @var \AppBundle\Entity\Billing\BillableItem $actualBillableItem */
        $actualBillableItem = $billableItems[0];

        $this->assertEquals(new \DateTime('2017-03-26 18:37:01', new \DateTimeZone('UTC')), $actualBillableItem->getTimewindowBegin());
    }
}
