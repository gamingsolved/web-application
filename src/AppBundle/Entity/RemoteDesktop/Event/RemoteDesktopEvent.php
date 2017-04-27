<?php

namespace AppBundle\Entity\RemoteDesktop\Event;

use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity
 * @ORM\Table(name="remote_desktop_events")
 */
class RemoteDesktopEvent
{
    // A desktop was made available so the user can actually connect to it
    const EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER = 0;
    const EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER  = 1;

    // Whether online or not, a desktop was generally made available, that is, the instance and ressources like disks
    // were created at the provider
    const EVENT_TYPE_DESKTOP_WAS_PROVISIONED_FOR_USER  = 2;
    const EVENT_TYPE_DESKTOP_WAS_UNPROVISIONED_FOR_USER  = 3;

    /**
     * @var string
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var RemoteDesktop
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\RemoteDesktop\RemoteDesktop", inversedBy="remoteDesktopEvents")
     * @ORM\JoinColumn(name="remote_desktops_id", referencedColumnName="id", nullable=false)
     */
    protected $remoteDesktop;

    /**
     * @var int
     * @ORM\Column(name="event_type", type="smallint", nullable=false)
     */
    protected $eventType;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="datetime_occured", type="datetime", nullable=false)
     */
    protected $datetimeOccured;


    public function __construct(RemoteDesktop $remoteDesktop, int $eventType, \DateTime $datetimeOccured)
    {
        $this->id = Uuid::uuid4();

        $this->remoteDesktop = $remoteDesktop;

        if ($eventType < self::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER || $eventType > self::EVENT_TYPE_DESKTOP_WAS_UNPROVISIONED_FOR_USER) {
            throw new \Exception('Event type ' . $eventType . ' is invalid');
        }
        $this->eventType = $eventType;

        if ($datetimeOccured->getTimezone()->getName() !== 'UTC') {
            throw new \Exception('Provided time zone is not UTC.');
        }
        $this->datetimeOccured = clone($datetimeOccured);
    }

    public function getId() : string
    {
        return $this->id;
    }

    public function getRemoteDesktop() : RemoteDesktop
    {
        return $this->remoteDesktop;
    }

    public function getEventType() : int
    {
        return $this->eventType;
    }

    public function getDatetimeOccured() : \DateTime
    {
        if ($this->datetimeOccured->getTimezone()->getName() !== 'UTC') {
            throw new \Exception('Stored time zone is not UTC, but ' . $this->datetimeOccured->getTimezone()->getName());
        }
        return clone($this->datetimeOccured);
    }

}
