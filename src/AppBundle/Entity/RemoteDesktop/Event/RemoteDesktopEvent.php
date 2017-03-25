<?php

namespace AppBundle\Entity\RemoteDesktop\Event;

use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="remote_desktop_events")
 */
class RemoteDesktopEvent
{
    const EVENT_TYPE_LAUNCHED = 1;
    const EVENT_TYPE_STOPPED  = 2;
    const EVENT_TYPE_STARTED  = 3;

    /**
     * @var string
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var RemoteDesktop
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\RemoteDesktop\RemoteDesktop", inversedBy="remoteDesktopEvents" , nullable=false)
     * @ORM\JoinColumn(name="remote_desktops_id", referencedColumnName="id")
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


    public function setId(string $id) : void
    {
        $this->id = $id;
    }

    public function getId() : string
    {
        return $this->id;
    }

    public function setRemoteDesktop(RemoteDesktop $remoteDesktop)
    {
        $this->remoteDesktop = $remoteDesktop;
    }

    public function setEventType(int $eventType)
    {
        if ($eventType < self::EVENT_TYPE_LAUNCHED || $eventType > self::EVENT_TYPE_STARTED) {
            throw new \Exception('Event type ' . $eventType . ' is invalid');
        }
        $this->eventType = $eventType;
    }

    public function getEventType() : int
    {
        return $this->eventType;
    }

    public function setDatetimeOccured(\DateTime $datetimeOccured) : void
    {
        if ($datetimeOccured->getTimezone()->getName() !== 'UTC') {
            throw new \Exception('Provided time zone is not UTC.');
        }
        $this->datetimeOccured = $datetimeOccured;
    }

    public function getDatetimeOccured() : \DateTime
    {
        if ($this->datetimeOccured->getTimezone()->getName() !== 'UTC') {
            throw new \Exception('Stored time zone is not UTC.');
        }
        return $this->datetimeOccured;
    }

}
