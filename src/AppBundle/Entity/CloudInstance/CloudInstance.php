<?php

namespace AppBundle\Entity\CloudInstance;

use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProviderInterface;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Flavor;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Image;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;

interface CloudInstanceInterface
{
    public function getId() : string;

    public function getCloudInstanceProvider() : CloudInstanceProviderInterface;

    public function setStatus(int $status);
    public function getStatus() : int;

    public function setRunstatus(int $runstatus);
    public function getRunstatus() : int;

    public function setFlavor(Flavor $flavor);
    public function getFlavor() : Flavor;

    public function setImage(Image $image);
    public function getImage() : Image;

    public function setRegion(Region $region);
    public function getRegion() : Region;

    public function setRemoteDesktop(RemoteDesktop $remoteDesktop);
}

abstract class CloudInstance implements CloudInstanceInterface
{
    const STATUS_IN_USE = 0;
    const STATUS_ARCHIVED = 1;

    const RUNSTATUS_SCHEDULED_FOR_LAUNCH = 0;
    const RUNSTATUS_LAUNCHING = 1;
    const RUNSTATUS_RUNNING = 2;
    const RUNSTATUS_SCHEDULED_FOR_SHUTDOWN = 3;
    const RUNSTATUS_SHUTTING_DOWN = 4;
    const RUNSTATUS_SHUT_DOWN = 5;

    public static function getStatusName(int $status) : string {
        switch ($status) {
            case self::STATUS_IN_USE:
                return 'in use';
                break;
            case self::STATUS_ARCHIVED:
                return 'archived';
                break;
        }
        return 'Could not resolve status to name';
    }

    public static function getRunstatusName(int $runstatus) : string {
        switch ($runstatus) {
            case self::RUNSTATUS_SCHEDULED_FOR_LAUNCH:
                return 'scheduled for launch';
                break;
            case self::RUNSTATUS_LAUNCHING:
                return 'launching';
                break;
            case self::RUNSTATUS_RUNNING:
                return 'running';
                break;
            case self::RUNSTATUS_SCHEDULED_FOR_SHUTDOWN:
                return 'scheduled for shutdown';
                break;
            case self::RUNSTATUS_SHUTTING_DOWN:
                return 'shutting down';
                break;
            case self::RUNSTATUS_SHUT_DOWN:
                return 'shut down';
                break;
        }
        return 'Could not resolve runstatus to name';
    }
}
