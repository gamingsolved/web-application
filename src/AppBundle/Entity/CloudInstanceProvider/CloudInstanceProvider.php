<?php

namespace AppBundle\Entity\CloudInstanceProvider;

use AppBundle\Entity\CloudInstance\CloudInstanceInterface;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Flavor;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Image;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;

interface CloudInstanceProviderInterface
{
    public function getRegions() : array;

    public function createInstanceForRemoteDesktopAndRegion(RemoteDesktop $remoteDesktop, Region $region) : CloudInstanceInterface;
}

abstract class CloudInstanceProvider implements CloudInstanceProviderInterface
{
    const AWS = 0;
}
