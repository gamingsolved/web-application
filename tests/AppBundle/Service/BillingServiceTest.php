<?php

namespace Tests\AppBundle\Service;

use AppBundle\Entity\Billing\BillableItem;
use AppBundle\Entity\CloudInstanceProvider\AwsCloudInstanceProvider;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEvent;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Entity\RemoteDesktop\RemoteDesktopGamingProKind;
use AppBundle\Service\BillingService;
use AppBundle\Utility\DateTimeUtility;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;


class BillingServiceTest extends TestCase
{

    protected function getRemoteDesktop() : RemoteDesktop
    {
        $remoteDesktop = new RemoteDesktop();
        $remoteDesktop->setCloudInstanceProvider(new AwsCloudInstanceProvider());
        $remoteDesktop->setId('r1');
        $remoteDesktop->setKind(new RemoteDesktopGamingProKind());
        $awsCloudInstanceProvider = new AwsCloudInstanceProvider();
        $remoteDesktop->addCloudInstance(
            $awsCloudInstanceProvider->createInstanceForRemoteDesktopAndRegion(
                $remoteDesktop,
                $awsCloudInstanceProvider->getRegionByInternalName('eu-central-1')
            )
        );
        return $remoteDesktop;
    }

    public function testNoBillableItemsForRemoteDesktopWithoutEvents()
    {
        $remoteDesktop = $this->getRemoteDesktop();

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
        $remoteDesktop = $this->getRemoteDesktop();

        $event = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER,
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
            ->getMockBuilder(EntityRepository::class)
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


    public function testOneBillableItemForMultipleTimesLaunchedAndStoppedRemoteDesktop()
    {
        $remoteDesktop = $this->getRemoteDesktop();

        $finishedLaunchingEvent1 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-26 18:37:01')
        );

        $beganStoppingEvent1 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-26 18:39:01')
        );

        $finishedLaunchingEvent2 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-26 19:20:00')
        );

        $beganStoppingEvent2 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-26 19:30:00')
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
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $billableItemRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['timewindowBegin' => 'DESC'])
            ->willReturn(null);

        $bs = new BillingService($remoteDesktopEventRepo, $billableItemRepo);

        $billableItems = $bs->generateMissingBillableItems($remoteDesktop, DateTimeUtility::createDateTime('2017-03-26 22:40:00'));

        $this->assertCount(1, $billableItems);

        /** @var \AppBundle\Entity\Billing\BillableItem $actualBillableItem */
        $actualBillableItem = $billableItems[0];

        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 18:37:01'), $actualBillableItem->getTimewindowBegin());
    }


    public function testThreeBillableItemsForMultipleTimesLaunchedAndStoppedRemoteDesktop()
    {
        $remoteDesktop = $this->getRemoteDesktop();

        $finishedLaunchingEvent1 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-26 18:37:01')
        );

        $beganStoppingEvent1 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-26 18:39:01')
        );

        $finishedLaunchingEvent2 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-26 19:20:00')
        );

        $beganStoppingEvent2 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-26 20:37:01') // Within next-next usage hour
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
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $billableItemRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['timewindowBegin' => 'DESC'])
            ->willReturn(null);

        $bs = new BillingService($remoteDesktopEventRepo, $billableItemRepo);

        $billableItems = $bs->generateMissingBillableItems($remoteDesktop, DateTimeUtility::createDateTime('2017-03-26 22:40:00'));

        $this->assertCount(3, $billableItems);

        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 18:37:01'), $billableItems[0]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 19:37:01'), $billableItems[1]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 20:37:01'), $billableItems[2]->getTimewindowBegin());
    }


    public function testSevenBillableItemsForLaunchedAndStoppedRemoteDesktop()
    {
        $remoteDesktop = $this->getRemoteDesktop();

        $finishedLaunchingEvent1 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-26 18:37:01')
        );

        $beganStoppingEvent1 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-27 00:37:01')
        );

        $remoteDesktopEventRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $remoteDesktopEventRepo->expects($this->once())
            ->method('findBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['datetimeOccured' => 'ASC'])
            ->willReturn([$finishedLaunchingEvent1, $beganStoppingEvent1]);

        $billableItemRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $billableItemRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['timewindowBegin' => 'DESC'])
            ->willReturn(null);

        $bs = new BillingService($remoteDesktopEventRepo, $billableItemRepo);

        $billableItems = $bs->generateMissingBillableItems($remoteDesktop, DateTimeUtility::createDateTime('2017-03-27 15:40:00'));

        $this->assertCount(7, $billableItems);

        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 18:37:01'), $billableItems[0]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 19:37:01'), $billableItems[1]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 20:37:01'), $billableItems[2]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 21:37:01'), $billableItems[3]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 22:37:01'), $billableItems[4]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 23:37:01'), $billableItems[5]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-27 00:37:01'), $billableItems[6]->getTimewindowBegin());
    }


    public function testOnlySixBillableItemsForLaunchedAndStoppedRemoteDesktopIfOneBillingItemAlreadyExists()
    {
        $remoteDesktop = $this->getRemoteDesktop();

        $finishedLaunchingEvent1 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-26 18:37:01')
        );

        $beganStoppingEvent1 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-27 00:37:01')
        );

        $remoteDesktopEventRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $remoteDesktopEventRepo->expects($this->once())
            ->method('findBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['datetimeOccured' => 'ASC'])
            ->willReturn([$finishedLaunchingEvent1, $beganStoppingEvent1]);

        $latestExistingBillableItem = new BillableItem(
            $remoteDesktop,
            DateTimeUtility::createDateTime('2017-03-26 18:37:01')
        );

        $billableItemRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $billableItemRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['timewindowBegin' => 'DESC'])
            ->willReturn($latestExistingBillableItem);

        $bs = new BillingService($remoteDesktopEventRepo, $billableItemRepo);

        $billableItems = $bs->generateMissingBillableItems($remoteDesktop, DateTimeUtility::createDateTime('2017-03-27 15:40:00'));

        $this->assertCount(6, $billableItems);

        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 19:37:01'), $billableItems[0]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 20:37:01'), $billableItems[1]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 21:37:01'), $billableItems[2]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 22:37:01'), $billableItems[3]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 23:37:01'), $billableItems[4]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-27 00:37:01'), $billableItems[5]->getTimewindowBegin());
    }


    public function testOnlyOneBillableItemForLaunchedAndStoppedRemoteDesktopIfSixBillingItemsAlreadyExist()
    {
        $remoteDesktop = $this->getRemoteDesktop();

        $finishedLaunchingEvent1 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-26 18:37:01')
        );

        $beganStoppingEvent1 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-27 00:37:01')
        );

        $remoteDesktopEventRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $remoteDesktopEventRepo->expects($this->once())
            ->method('findBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['datetimeOccured' => 'ASC'])
            ->willReturn([$finishedLaunchingEvent1, $beganStoppingEvent1]);

        $latestExistingBillableItem = new BillableItem(
            $remoteDesktop,
            DateTimeUtility::createDateTime('2017-03-26 23:37:01')
        );

        $billableItemRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $billableItemRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['timewindowBegin' => 'DESC'])
            ->willReturn($latestExistingBillableItem);

        $bs = new BillingService($remoteDesktopEventRepo, $billableItemRepo);

        $billableItems = $bs->generateMissingBillableItems($remoteDesktop, DateTimeUtility::createDateTime('2017-03-27 15:40:00'));

        $this->assertCount(1, $billableItems);

        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-27 00:37:01'), $billableItems[0]->getTimewindowBegin());
    }


    public function testNoBillableItemForLaunchedAndStoppedRemoteDesktopIfAllBillingItemsAlreadyExist()
    {
        $remoteDesktop = $this->getRemoteDesktop();

        $finishedLaunchingEvent1 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-26 18:37:01')
        );

        $beganStoppingEvent1 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-27 00:37:01')
        );

        $remoteDesktopEventRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $remoteDesktopEventRepo->expects($this->once())
            ->method('findBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['datetimeOccured' => 'ASC'])
            ->willReturn([$finishedLaunchingEvent1, $beganStoppingEvent1]);

        $latestExistingBillableItem = new BillableItem(
            $remoteDesktop,
            DateTimeUtility::createDateTime('2017-03-27 00:37:01')
        );

        $billableItemRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $billableItemRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['timewindowBegin' => 'DESC'])
            ->willReturn($latestExistingBillableItem);

        $bs = new BillingService($remoteDesktopEventRepo, $billableItemRepo);

        $billableItems = $bs->generateMissingBillableItems($remoteDesktop, DateTimeUtility::createDateTime('2017-03-27 15:40:00'));

        $this->assertCount(0, $billableItems);
    }


    public function testTwoBillableItemsForRemoteDesktopLaunchedMoreThanOneHourAgo()
    {
        $remoteDesktop = $this->getRemoteDesktop();

        $event = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER,
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
            ->getMockBuilder(EntityRepository::class)
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
        $remoteDesktop = $this->getRemoteDesktop();

        $finishedLaunchingEvent = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-26 18:37:01')
        );

        $beganStoppingEvent = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER,
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
            ->getMockBuilder(EntityRepository::class)
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
        $remoteDesktop = $this->getRemoteDesktop();

        $finishedLaunchingEvent = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-26 18:37:01')
        );

        $beganStoppingEvent = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER,
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
            ->getMockBuilder(EntityRepository::class)
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
        $remoteDesktop = $this->getRemoteDesktop();

        $finishedLaunchingEvent = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-26 18:37:01')
        );

        $beganStoppingEvent = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER,
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
            ->getMockBuilder(EntityRepository::class)
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
        $remoteDesktop = $this->getRemoteDesktop();

        $finishedLaunchingEvent1 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-26 18:37:01')
        );

        $beganStoppingEvent1 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-26 19:20:01')
        );

        $finishedLaunchingEvent2 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-29 21:15:00')
        );

        $beganStoppingEvent2 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER,
            DateTimeUtility::createDateTime('2017-03-29 22:10:05')
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
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $billableItemRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['timewindowBegin' => 'DESC'])
            ->willReturn(null);

        $bs = new BillingService($remoteDesktopEventRepo, $billableItemRepo);

        // We ask to only work up to a point in time that is not more than one hour away from the start event - thus
        // we expect to not learn about the prolongation
        $billableItems = $bs->generateMissingBillableItems($remoteDesktop, DateTimeUtility::createDateTime('2017-03-29 22:30:00'));

        $this->assertCount(2, $billableItems);

        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 18:37:01'), $billableItems[0]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-29 21:15:00'), $billableItems[1]->getTimewindowBegin());
    }

}
