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

    public function getHourlyCosts() : float;

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
    public function getRemoteDesktop() : RemoteDesktop;

    public function setAdminPassword(string $password);
    public function getAdminPassword() : string;

    public function setPublicAddress(string $addr);
    public function getPublicAddress() : string;

    public function getProviderInstanceId() : string;
}

abstract class CloudInstance implements CloudInstanceInterface
{
    const STATUS_IN_USE = 0;
    const STATUS_ARCHIVED = 1;

    const RUNSTATUS_SCHEDULED_FOR_LAUNCH = 0;
    const RUNSTATUS_LAUNCHING = 1;
    const RUNSTATUS_RUNNING = 2;
    const RUNSTATUS_SCHEDULED_FOR_STOP = 3;
    const RUNSTATUS_STOPPING = 4;
    const RUNSTATUS_STOPPED = 5;
    const RUNSTATUS_SCHEDULED_FOR_START = 6;
    const RUNSTATUS_STARTING = 7;
    const RUNSTATUS_SCHEDULED_FOR_TERMINATION = 8;
    const RUNSTATUS_TERMINATING = 9;
    const RUNSTATUS_TERMINATED = 10;

    const ADMIN_PASSWORD_ENCRYPTION_KEY = '06c528c143c3f5c73ae200048782bd422a4f1b90';

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
            case self::RUNSTATUS_SCHEDULED_FOR_STOP:
                return 'scheduled for stop';
                break;
            case self::RUNSTATUS_STOPPING:
                return 'stopping';
                break;
            case self::RUNSTATUS_STOPPED:
                return 'stopped';
                break;
            case self::RUNSTATUS_SCHEDULED_FOR_START:
                return 'scheduled for start';
                break;
            case self::RUNSTATUS_STARTING;
                return 'starting';
                break;
            case self::RUNSTATUS_SCHEDULED_FOR_TERMINATION:
                return 'scheduled for termination';
                break;
            case self::RUNSTATUS_TERMINATING:
                return 'terminating';
                break;
            case self::RUNSTATUS_TERMINATED:
                return 'terminated';
                break;

        }
        return 'Could not resolve runstatus to name';
    }
}
