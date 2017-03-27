<?php

namespace AppBundle\Entity\Billing;

use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEvent;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="billable_items")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\Billing\BillableItemRepository")
 */
class BillableItem
{
    const ITEM_TYPE_REMOTEDESKTOPUSAGE = 1;
    const BILLABLE_TIMEWINDOW_REMOTEDESKTOPUSAGE = 3600; // A minimum of 1 hour is billed when using a remote desktop

    /**
     * @var string
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var RemoteDesktop
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\RemoteDesktop\RemoteDesktop", inversedBy="billableItems")
     * @ORM\JoinColumn(name="remote_desktops_id", referencedColumnName="id", nullable=false)
     */
    protected $remoteDesktop;

    /**
     * @var int
     * @ORM\Column(name="item_type", type="smallint", nullable=false)
     */
    protected $itemType;

    /**
     * @var \DateTime $timewindowBegin The begin of the window for which this item covers costs - inclusive
     *
     * @ORM\Column(name="timewindow_begin", type="datetime", nullable=false)
     */
    protected $timewindowBegin;

    /**
     * @var \DateTime $timewindowEnd The end of the window for which this item covers costs - not inclusive
     *
     * @ORM\Column(name="timewindow_end", type="datetime", nullable=false)
     */
    protected $timewindowEnd;

    /**
     * @var ArrayCollection|\AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEvent
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\CloudInstance\AwsCloudInstance", mappedBy="billableItem", cascade="all")
     */
    protected $relatedRemoteDesktopEvents;


    public function __construct(\DateTime $timewindowBegin, array $relatedRemoteDesktopEvents) {
        $this->relatedRemoteDesktopEvents = new ArrayCollection();

        /** @var RemoteDesktopEvent $remoteDesktopEvent */
        foreach ($relatedRemoteDesktopEvents as $remoteDesktopEvent) {
            if (get_class($remoteDesktopEvent) !== RemoteDesktopEvent::class) {
                throw new \Exception('This is not an instance of class RemoteDesktopEvent: ' . (string)$remoteDesktopEvent);
            }
            $this->relatedRemoteDesktopEvents->add($remoteDesktopEvent);
        }

        if ($timewindowBegin->getTimezone()->getName() !== 'UTC') {
            throw new \Exception('Provided time zone is not UTC.');
        }
        $this->timewindowBegin = clone($timewindowBegin);

        $this->timewindowEnd = clone($timewindowBegin);
        $this->timewindowEnd = $this->timewindowEnd->add(new \DateInterval('PT' . self::BILLABLE_TIMEWINDOW_REMOTEDESKTOPUSAGE . 'S'));
    }

    public function getTimewindowBegin() : \DateTime
    {
        if ($this->timewindowBegin->getTimezone()->getName() !== 'UTC') {
            throw new \Exception('Stored time zone is not UTC.');
        }
        return clone($this->timewindowBegin);
    }

    public function getTimewindowEnd() : \DateTime
    {
        if ($this->timewindowEnd->getTimezone()->getName() !== 'UTC') {
            throw new \Exception('Stored time zone is not UTC.');
        }
        return clone($this->timewindowEnd);
    }
}
