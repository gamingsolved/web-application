<?php

namespace AppBundle\Entity\CloudInstanceProvider;

use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Flavor;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Image;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;

interface CloudInstanceProviderInterface
{
    public function getFlavors() : array;

    public function getImages() : array;

    public function getRegions() : array;

    public function getFlavorByInternalName(string $flavorInternalName) : Flavor;

    public function getImageByInternalName(string $imageInternalName) : Image;

    public function getRegionByInternalName(string $regionInternalName) : Region;

    public function createInstanceForRemoteDesktopAndRegion(RemoteDesktop $remoteDesktop, Region $region) : CloudInstance;

    public function getUsageCostsInterval(): int;

    public function getProvisioningCostsInterval(): int;

    public function getUsageCostsForFlavorImageRegionCombinationForOneInterval(Flavor $flavor, Image $image, Region $region) : float;

    public function getProvisioningCostsForFlavorImageRegionVolumeSizesCombinationForOneInterval(
        Flavor $flavor, Image $image, Region $region, int $rootVolumeSize, int $additionalVolumeSize) : float;

    public function getMaximumUsageCostsForFlavorForOneInterval(Flavor $flavor) : float;

    public function hasLatencycheckEndpoints() : bool;
}

abstract class CloudInstanceProvider implements CloudInstanceProviderInterface
{
    const PROVIDER_AWS = 0;
    const PROVIDER_PAPERSPACE = 1;

    protected $usageCostsInterval = null;
    protected $provisioningCostsInterval = null;

    /**
     * @throws \Exception
     */
    public function getFlavorByInternalName(string $flavorInternalName) : Flavor
    {
        $flavors = $this->getFlavors();
        foreach ($flavors as $flavor) {
            if ($flavor->getInternalName() == $flavorInternalName) {
                return $flavor;
            }
        }
        throw new \Exception('Could not find flavor with internal name ' . $flavorInternalName);
    }

    public function getImageByInternalName(string $imageInternalName) : Image
    {
        $images = $this->getImages();
        foreach ($images as $image) {
            if ($image->getInternalName() == $imageInternalName) {
                return $image;
            }
        }
        throw new \Exception('Could not find image with internal name ' . $imageInternalName);
    }

    public function getRegionByInternalName(string $regionInternalName) : Region
    {
        $regions = $this->getRegions();
        foreach ($regions as $region) {
            if ($region->getInternalName() == $regionInternalName) {
                return $region;
            }
        }
        throw new \Exception('Could not find region with internal name ' . $regionInternalName);
    }

    public function getUsageCostsInterval(): int
    {
        return $this->usageCostsInterval;
    }

    public function getProvisioningCostsInterval(): int
    {
        return $this->provisioningCostsInterval;
    }

}
