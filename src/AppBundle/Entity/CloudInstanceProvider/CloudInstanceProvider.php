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

    public function getRegionByInternalName(string $regionInternalName) : Region;

    public function createInstanceForRemoteDesktopAndRegion(RemoteDesktop $remoteDesktop, Region $region) : CloudInstanceInterface;
}

abstract class CloudInstanceProvider implements CloudInstanceProviderInterface
{
    const AWS = 0;

    /**
     * @throws \Exception
     */
    public function getRegionByInternalName(string $regionInternalName) : Region
    {
        $regions = $this->getRegions();
        foreach ($regions as $region) {
            if ($region->getInternalName() == $regionInternalName) {
                return $region;
            }
        }
        throw new \Exception('Could not find region with internal name' . $regionInternalName);
    }
}
