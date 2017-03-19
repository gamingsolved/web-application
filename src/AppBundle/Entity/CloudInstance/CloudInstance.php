<?php

namespace AppBundle\Entity\CloudInstance;

use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProviderInterface;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Flavor;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Image;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;

interface CloudInstanceInterface
{
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
}
