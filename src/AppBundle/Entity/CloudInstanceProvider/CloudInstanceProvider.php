<?php

namespace AppBundle\Entity\CloudInstanceProvider;

interface CloudInstanceProviderInterface
{
    /**
     * @return Region[]
     */
    public function getRegions() : array;

    /**
     * @param string $regionInternalName
     * @return Region
     */
    public function getRegionByInternalName(string $regionInternalName) : Region;

    /**
     * @return Flavor[]
     */
    public function getFlavors() : array;

    /**
     * @return Image[]
     */
    public function getImages() : array;
}

abstract class CloudInstanceProvider implements CloudInstanceProviderInterface
{
    const CLOUD_INSTANCE_PROVIDER_AWS_ID = 0;

    /**
     * @param string $regionInternalName
     * @return Region
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
