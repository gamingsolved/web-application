<?php

namespace AppBundle\Entity\Billing;

use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="billable_items")
 */
class BillableItem
{
    const BILLABLE_TIMEWINDOW_HOURLY = 3600; // For hourly usage or provisioning costs
    const BILLABLE_TIMEWINDOW_MONTHLY = 2592000; // For monthly usage or provisioning costs - exactly 30 days

    const TYPE_USAGE = 0;
    const TYPE_PROVISIONING = 1;

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
     * @ORM\Column(name="type", type="smallint", nullable=false)
     */
    protected $type;

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
     * @var float
     * @ORM\Column(name="price", type="float", nullable=false)
     */
    protected $price;


    public function __construct(RemoteDesktop $remoteDesktop, \DateTime $timewindowBegin, int $type) {
        if ($timewindowBegin->getTimezone()->getName() !== 'UTC') {
            throw new \Exception('Provided time zone is not UTC.');
        }

        if ($type < self::TYPE_USAGE || $type > self::TYPE_PROVISIONING) {
            throw new \Exception('Invalid type ' . $type);
        }

        $this->timewindowBegin = clone($timewindowBegin);

        $this->timewindowEnd = clone($timewindowBegin);

        if ($type === self::TYPE_USAGE) {
            if ($remoteDesktop->getUsageCostsInterval() === RemoteDesktop::COSTS_INTERVAL_HOURLY) {
                $this->timewindowEnd = $this->timewindowEnd->add(new \DateInterval('PT' . self::BILLABLE_TIMEWINDOW_HOURLY . 'S'));
            } elseif ($remoteDesktop->getUsageCostsInterval() === RemoteDesktop::COSTS_INTERVAL_MONTHLY) {
                $this->timewindowEnd = $this->timewindowEnd->add(new \DateInterval('PT' . self::BILLABLE_TIMEWINDOW_MONTHLY . 'S'));
            } else {
                throw new \Exception(
                    'Invalid usage costs interval ' . $remoteDesktop->getUsageCostsInterval() . ' of remote desktop ' . $remoteDesktop->getId()
                );
            }
        }

        if ($type === self::TYPE_PROVISIONING) {
            if ($remoteDesktop->getProvisioningCostsInterval() === RemoteDesktop::COSTS_INTERVAL_HOURLY) {
                $this->timewindowEnd = $this->timewindowEnd->add(new \DateInterval('PT' . self::BILLABLE_TIMEWINDOW_HOURLY . 'S'));
            } elseif ($remoteDesktop->getProvisioningCostsInterval() === RemoteDesktop::COSTS_INTERVAL_MONTHLY) {
                $this->timewindowEnd = $this->timewindowEnd->add(new \DateInterval('PT' . self::BILLABLE_TIMEWINDOW_MONTHLY . 'S'));
            } else {
                throw new \Exception(
                    'Invalid provisioning costs interval ' . $remoteDesktop->getProvisioningCostsInterval() . ' of remote desktop ' . $remoteDesktop->getId()
                );
            }
        }

        $this->remoteDesktop = $remoteDesktop;

        $this->type = $type;

        if ($this->type === self::TYPE_USAGE) {
            $this->price = $remoteDesktop->getUsageCostsForOneInterval();
        }

        if ($this->type === self::TYPE_PROVISIONING) {
            $this->price = $remoteDesktop->getProvisioningCostsForOneInterval();
        }

        if ($this->price < 0.0) {
            throw new \Exception('Negative price of ' . $this->price . ' is invalid.');
        }
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

    public function getPrice() : float
    {
        return $this->price;
    }

    public function getType() : int
    {
        return $this->type;
    }

    public function getRemoteDesktop() : RemoteDesktop
    {
        return $this->remoteDesktop;
    }
}
