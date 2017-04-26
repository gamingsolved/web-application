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

class BillingServiceProvisioningBillingTest extends TestCase
{

    protected function getRemoteDesktop() : RemoteDesktop
    {
        $remoteDesktop = new RemoteDesktop();
        $remoteDesktop->setCloudInstanceProvider(new AwsCloudInstanceProvider());
        $remoteDesktop->setId('r1');
        $remoteDesktop->setKind(new RemoteDesktopGamingProKind());
        $cloudInstanceProvider = $remoteDesktop->getKind()->getCloudInstanceProvider();
        $remoteDesktop->addCloudInstance(
            $cloudInstanceProvider->createInstanceForRemoteDesktopAndRegion(
                $remoteDesktop,
                $cloudInstanceProvider->getRegionByInternalName('eu-central-1')
            )
        );
        return $remoteDesktop;
    }


    public function testNoProvisioningBillableItemsForRemoteDesktopWithoutProvisioningEvents()
    {
        $remoteDesktop = $this->getRemoteDesktop();

        // For provisioning billing, this event type must be ignored
        $usageEvent = new RemoteDesktopEvent(
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
            ->willReturn([$usageEvent]);

        $billableItemRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $bs = new BillingService($remoteDesktopEventRepo, $billableItemRepo);

        $billableItems = $bs->generateMissingBillableItems(
            $remoteDesktop,
            DateTimeUtility::createDateTime('now'),
            BillableItem::TYPE_PROVISIONING
        );

        $this->assertEmpty($billableItems);
    }


    public function testOneProvisioningBillableItemForLaunchedRemoteDesktop()
    {
        $remoteDesktop = $this->getRemoteDesktop();

        $events = [
            new RemoteDesktopEvent(
                $remoteDesktop,
                RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER,
                DateTimeUtility::createDateTime('2017-03-26 18:37:01')
            ),
            new RemoteDesktopEvent(
                $remoteDesktop,
                RemoteDesktopEvent::EVENT_TYPE_DESKTOP_WAS_PROVISIONED_FOR_USER,
                DateTimeUtility::createDateTime('2017-03-26 18:37:01')
            ),
        ];

        $remoteDesktopEventRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $remoteDesktopEventRepo->expects($this->once())
            ->method('findBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['datetimeOccured' => 'ASC'])
            ->willReturn($events);

        $billableItemRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $billableItemRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['remoteDesktop' => $remoteDesktop, 'type' => BillableItem::TYPE_PROVISIONING], ['timewindowBegin' => 'DESC'])
            ->willReturn(null);

        $bs = new BillingService($remoteDesktopEventRepo, $billableItemRepo);

        $billableItems = $bs->generateMissingBillableItems(
            $remoteDesktop,
            DateTimeUtility::createDateTime('2017-03-26 18:40:00'),
            BillableItem::TYPE_PROVISIONING
        );

        $this->assertCount(1, $billableItems);

        /** @var \AppBundle\Entity\Billing\BillableItem $actualBillableItem */
        $actualBillableItem = $billableItems[0];

        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 18:37:01'), $actualBillableItem->getTimewindowBegin());
        $this->assertEquals(0.04, $actualBillableItem->getPrice());
        $this->assertEquals(BillableItem::TYPE_PROVISIONING, $actualBillableItem->getType());
    }


    public function testTwoProvisioningBillableItemsForRemoteDesktopLaunchedMoreThanOneHourAgo()
    {
        $remoteDesktop = $this->getRemoteDesktop();

        $events = [
            new RemoteDesktopEvent(
                $remoteDesktop,
                RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER,
                DateTimeUtility::createDateTime('2017-03-26 18:37:01')
            ),
            new RemoteDesktopEvent(
                $remoteDesktop,
                RemoteDesktopEvent::EVENT_TYPE_DESKTOP_WAS_PROVISIONED_FOR_USER,
                DateTimeUtility::createDateTime('2017-03-26 18:37:01')
            ),

            // The fact that a desktop is stopped must not be relevant for provisioning billing
            new RemoteDesktopEvent(
                $remoteDesktop,
                RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER,
                DateTimeUtility::createDateTime('2017-03-26 18:38:01')
            ),
        ];

        $remoteDesktopEventRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $remoteDesktopEventRepo->expects($this->once())
            ->method('findBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['datetimeOccured' => 'ASC'])
            ->willReturn($events);

        $billableItemRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $billableItemRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['remoteDesktop' => $remoteDesktop, 'type' => BillableItem::TYPE_PROVISIONING], ['timewindowBegin' => 'DESC'])
            ->willReturn(null);

        $bs = new BillingService($remoteDesktopEventRepo, $billableItemRepo);

        /** @var BillableItem[] $billableItems */
        $billableItems = $bs->generateMissingBillableItems(
            $remoteDesktop,
            DateTimeUtility::createDateTime('2017-03-26 19:40:00'),
            BillableItem::TYPE_PROVISIONING
        );

        $this->assertCount(2, $billableItems);

        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 18:37:01'), $billableItems[0]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 19:37:01'), $billableItems[1]->getTimewindowBegin());
    }


    public function testNoProvisioningBillableItemForProvisionedAndUnprovisionedRemoteDesktopIfAllBillingItemsAlreadyExist()
    {
        $remoteDesktop = $this->getRemoteDesktop();

        $finishedLaunchingEvent1 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_WAS_PROVISIONED_FOR_USER,
            DateTimeUtility::createDateTime('2017-03-26 18:37:01')
        );

        $beganStoppingEvent1 = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_WAS_UNPROVISIONED_FOR_USER,
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
            DateTimeUtility::createDateTime('2017-03-27 00:37:01'),
            BillableItem::TYPE_PROVISIONING
        );

        $billableItemRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $billableItemRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['remoteDesktop' => $remoteDesktop, 'type' => BillableItem::TYPE_PROVISIONING], ['timewindowBegin' => 'DESC'])
            ->willReturn($latestExistingBillableItem);

        $bs = new BillingService($remoteDesktopEventRepo, $billableItemRepo);

        /** @var BillableItem[] $billableItems */
        $billableItems = $bs->generateMissingBillableItems(
            $remoteDesktop,
            DateTimeUtility::createDateTime('2017-03-27 15:40:00'),
            BillableItem::TYPE_PROVISIONING
        );

        $this->assertCount(0, $billableItems);
    }


    public function testSevenProvisioningBillableItemsForProvisionedAndUnprovisionedRemoteDesktop()
    {
        $remoteDesktop = $this->getRemoteDesktop();

        $provisioningEvent = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_WAS_PROVISIONED_FOR_USER,
            DateTimeUtility::createDateTime('2017-03-26 18:37:01')
        );

        $unprovisioningEvent = new RemoteDesktopEvent(
            $remoteDesktop,
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_WAS_UNPROVISIONED_FOR_USER,
            DateTimeUtility::createDateTime('2017-03-27 00:37:01')
        );

        $remoteDesktopEventRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $remoteDesktopEventRepo->expects($this->once())
            ->method('findBy')
            ->with(['remoteDesktop' => $remoteDesktop], ['datetimeOccured' => 'ASC'])
            ->willReturn([$provisioningEvent, $unprovisioningEvent]);

        $billableItemRepo = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $billableItemRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['remoteDesktop' => $remoteDesktop, 'type' => BillableItem::TYPE_PROVISIONING], ['timewindowBegin' => 'DESC'])
            ->willReturn(null);

        $bs = new BillingService($remoteDesktopEventRepo, $billableItemRepo);

        /** @var BillableItem[] $billableItems */
        $billableItems = $bs->generateMissingBillableItems(
            $remoteDesktop,
            DateTimeUtility::createDateTime('2017-03-27 15:40:00'),
            BillableItem::TYPE_PROVISIONING
        );

        $this->assertCount(7, $billableItems);

        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 18:37:01'), $billableItems[0]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 19:37:01'), $billableItems[1]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 20:37:01'), $billableItems[2]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 21:37:01'), $billableItems[3]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 22:37:01'), $billableItems[4]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-26 23:37:01'), $billableItems[5]->getTimewindowBegin());
        $this->assertEquals(DateTimeUtility::createDateTime('2017-03-27 00:37:01'), $billableItems[6]->getTimewindowBegin());
    }
}
