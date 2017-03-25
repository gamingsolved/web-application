<?php

namespace AppBundle\Entity\Billing;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="billable_items")
 */
class BillableItem
{
    const ITEM_TYPE_REMOTEDESKTOPUSAGE = 1;

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
     * @var ArrayCollection|\AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEvent
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\CloudInstance\AwsCloudInstance", mappedBy="billableItem", cascade="all")
     */
    protected $relatedRemoteDesktopEvents;


    public function __construct() {
        $this->relatedRemoteDesktopEvents = new ArrayCollection();
    }
}
