<?php

namespace Tests\AppBundle\Service;

use AppBundle\Entity\Billing\BillableItemRepository;
use AppBundle\Entity\Billing\BillableItemsRepositoryInterface;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEvent;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEventsRepositoryInterface;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Service\BillingService;
use AppBundle\Utility\DateTimeUtility;
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

        $billableItems = $bs->generateMissingBillableItems($remoteDesktop, DateTimeUtility::createDateTime('now'));

        $this->assertEmpty($billableItems);
    }

    public function testOneBillableItemForLaunchedRemoteDesktop()
    {
        $remoteDesktop = new RemoteDesktop();
        $remoteDesktop->setId('r1');

        $event = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_FINISHED_LAUNCHING,
            DateTimeUtility::createDateTime('2017-03-26 18:37:01')
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
            ->method('findOneBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['timewindowBegin' => 'DESC'])
            ->willReturn(null);

        $bs = new BillingService($remoteDesktopEventRepo, $billableItemRepo);

        $billableItems = $bs->generateMissingBillableItems($remoteDesktop, DateTimeUtility::createDateTime('2017-03-26 18:40:00'));

        $this->assertCount(1, $billableItems);

        /** @var \AppBundle\Entity\Billing\BillableItem $actualBillableItem */
        $actualBillableItem = $billableItems[0];

        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 18:37:01'), $actualBillableItem->getTimewindowBegin());
    }

    public function testTwoBillableItemsForRemoteDesktopLaunchedMoreThanOneHourAgo()
    {
        $remoteDesktop = new RemoteDesktop();
        $remoteDesktop->setId('r1');

        $event = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_FINISHED_LAUNCHING,
            DateTimeUtility::createDateTime('2017-03-26 18:37:01')
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
            ->method('findOneBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['timewindowBegin' => 'DESC'])
            ->willReturn(null);

        $bs = new BillingService($remoteDesktopEventRepo, $billableItemRepo);

        $billableItems = $bs->generateMissingBillableItems($remoteDesktop, DateTimeUtility::createDateTime('2017-03-26 19:40:00'));

        $this->assertCount(2, $billableItems);

        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 18:37:01'), $billableItems[0]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 19:37:01'), $billableItems[1]->getTimewindowBegin());
    }

    public function testOneBillableItemForRemoteDesktopLaunchedMoreThanOneHourAgoAndStoppedWithinOneHour()
    {
        $remoteDesktop = new RemoteDesktop();
        $remoteDesktop->setId('r1');

        $finishedLaunchingEvent = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_FINISHED_LAUNCHING,
            DateTimeUtility::createDateTime('2017-03-26 18:37:01')
        );

        $beganStoppingEvent = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BEGAN_STOPPING,
            DateTimeUtility::createDateTime('2017-03-26 19:37:00') // This is still considered as within the first usage hour
        );

        $remoteDesktopEventRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $remoteDesktopEventRepo->expects($this->once())
            ->method('findBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['datetimeOccured' => 'ASC'])
            ->willReturn([$finishedLaunchingEvent, $beganStoppingEvent]);

        $billableItemRepo = $this
            ->getMockBuilder(BillableItemRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $billableItemRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['timewindowBegin' => 'DESC'])
            ->willReturn(null);

        $bs = new BillingService($remoteDesktopEventRepo, $billableItemRepo);

        $billableItems = $bs->generateMissingBillableItems($remoteDesktop, DateTimeUtility::createDateTime('2017-03-26 19:40:00'));

        $this->assertCount(1, $billableItems);

        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 18:37:01'), $billableItems[0]->getTimewindowBegin());
    }

    public function testTwoBillableItemsForRemoteDesktopLaunchedMoreThanOneHourAgoAndStoppedMoreThanOneHourLater()
    {
        $remoteDesktop = new RemoteDesktop();
        $remoteDesktop->setId('r1');

        $finishedLaunchingEvent = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_FINISHED_LAUNCHING,
            DateTimeUtility::createDateTime('2017-03-26 18:37:01')
        );

        $beganStoppingEvent = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BEGAN_STOPPING,
            DateTimeUtility::createDateTime('2017-03-26 19:37:01') // This counts as the next usage hour, because the end date is exclusive
        );

        $remoteDesktopEventRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $remoteDesktopEventRepo->expects($this->once())
            ->method('findBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['datetimeOccured' => 'ASC'])
            ->willReturn([$finishedLaunchingEvent, $beganStoppingEvent]);

        $billableItemRepo = $this
            ->getMockBuilder(BillableItemRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $billableItemRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['timewindowBegin' => 'DESC'])
            ->willReturn(null);

        $bs = new BillingService($remoteDesktopEventRepo, $billableItemRepo);

        $billableItems = $bs->generateMissingBillableItems($remoteDesktop, DateTimeUtility::createDateTime('2017-03-26 23:40:00'));

        $this->assertCount(2, $billableItems);

        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 18:37:01'), $billableItems[0]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 19:37:01'), $billableItems[1]->getTimewindowBegin());
    }

    public function testOneBillableItemsForRemoteDesktopLaunchedWithinTheUptoHourAndStoppedMoreThanOneHourLater()
    {
        $remoteDesktop = new RemoteDesktop();
        $remoteDesktop->setId('r1');

        $finishedLaunchingEvent = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_FINISHED_LAUNCHING,
            DateTimeUtility::createDateTime('2017-03-26 18:37:01')
        );

        $beganStoppingEvent = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BEGAN_STOPPING,
            DateTimeUtility::createDateTime('2017-03-26 19:50:01') // This counts as the next usage hour, because the end date is exclusive
        );

        $remoteDesktopEventRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $remoteDesktopEventRepo->expects($this->exactly(2))
            ->method('findBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['datetimeOccured' => 'ASC'])
            ->willReturn([$finishedLaunchingEvent, $beganStoppingEvent]);

        $billableItemRepo = $this
            ->getMockBuilder(BillableItemRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $billableItemRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['timewindowBegin' => 'DESC'])
            ->willReturn(null);

        $bs = new BillingService($remoteDesktopEventRepo, $billableItemRepo);

        // We ask to only work up to a point in time that is not more than one hour away from the start event - thus
        // we expect to not learn about the prolongation
        $billableItems = $bs->generateMissingBillableItems($remoteDesktop, DateTimeUtility::createDateTime('2017-03-26 19:37:01'));

        $this->assertCount(1, $billableItems);

        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 18:37:01'), $billableItems[0]->getTimewindowBegin());


        // However, if we set up to to only one seconds into the hour that follows the hour from the beginning of the item
        // created by the start event, we expect to get the prolongation
        $billableItems = $bs->generateMissingBillableItems($remoteDesktop, DateTimeUtility::createDateTime('2017-03-26 19:37:02'));

        $this->assertCount(2, $billableItems);

        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 18:37:01'), $billableItems[0]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 19:37:01'), $billableItems[1]->getTimewindowBegin());
    }

    public function testTwoBillableItemsForTwoUsagesWithALargeGapBetweenThem()
    {
        $remoteDesktop = new RemoteDesktop();
        $remoteDesktop->setId('r1');

        $finishedLaunchingEvent1 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_FINISHED_LAUNCHING,
            DateTimeUtility::createDateTime('2017-03-26 18:37:01')
        );

        $beganStoppingEvent1 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BEGAN_STOPPING,
            DateTimeUtility::createDateTime('2017-03-26 19:20:01')
        );

        $finishedLaunchingEvent2 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_FINISHED_LAUNCHING,
            DateTimeUtility::createDateTime('2017-03-26 21:15:00')
        );

        $beganStoppingEvent2 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BEGAN_STOPPING,
            DateTimeUtility::createDateTime('2017-03-26 22:10:05')
        );

        $remoteDesktopEventRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $remoteDesktopEventRepo->expects($this->once())
            ->method('findBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['datetimeOccured' => 'ASC'])
            ->willReturn([$finishedLaunchingEvent1, $beganStoppingEvent1, $finishedLaunchingEvent2, $beganStoppingEvent2]);

        $billableItemRepo = $this
            ->getMockBuilder(BillableItemRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $billableItemRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['timewindowBegin' => 'DESC'])
            ->willReturn(null);

        $bs = new BillingService($remoteDesktopEventRepo, $billableItemRepo);

        // We ask to only work up to a point in time that is not more than one hour away from the start event - thus
        // we expect to not learn about the prolongation
        $billableItems = $bs->generateMissingBillableItems($remoteDesktop, DateTimeUtility::createDateTime('2017-03-26 22:30:00'));

        $this->assertCount(2, $billableItems);

        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 18:37:01'), $billableItems[0]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 21:15:00'), $billableItems[1]->getTimewindowBegin());
    }

}
