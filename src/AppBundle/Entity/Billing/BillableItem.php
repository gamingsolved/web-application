<?php

namespace AppBundle\Entity\Billing;

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
     * @var int
     * @ORM\Column(name="item_type", type="smallint", nullable=false)
     */
    protected $itemType;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timewindow_begin", type="datetime", nullable=false)
     */
    protected $timewindowBegin;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timewindow_end", type="datetime", nullable=false)
     */
    protected $timewindowEnd;

    /**
     * @var ArrayCollection|\AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEvent
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\CloudInstance\AwsCloudInstance", mappedBy="billableItem", cascade="all")
     */
    protected $relatedRemoteDesktopEvents;


    public function __construct() {
        $this->relatedRemoteDesktopEvents = new ArrayCollection();
    }
}
