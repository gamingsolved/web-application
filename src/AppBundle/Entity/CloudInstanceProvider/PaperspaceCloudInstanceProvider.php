<?php

namespace AppBundle\Entity\CloudInstanceProvider;

use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstance\PaperspaceCloudInstance;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Flavor;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Image;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Entity\RemoteDesktop\RemoteDesktopKind;

class PaperspaceCloudInstanceProvider extends CloudInstanceProvider
{
    protected $flavors = [];
    protected $images = [];
    protected $regions = [];

    protected $kindsToRegionsToImages = [];

    protected $usageCostsInterval = RemoteDesktop::COSTS_INTERVAL_HOURLY;
    protected $provisioningCostsInterval = RemoteDesktop::COSTS_INTERVAL_MONTHLY;

    public function __construct()
    {

        // Never remove a flavor, image or region, because there might still be users
        // who have old desktops with this flavor/image/region

        $this->flavors = [
            new Flavor($this, 'Air', '2 vCPUs, 4 GB RAM, 512 MB GPU'),
            new Flavor($this, 'GPU+', '8 vCPUs, 30 GB RAM, 8 GB NVIDIA® Quadro® M4000 GPU')
        ];

        $this->images = [
            new Image($this, 't6ixobq', 'Windows 10, all regions'),
            new Image($this, 't2q0g8n', 'Windows 10 with CGX v1, NY2'),
        ];


        $this->regions = [
            new Region($this, 'East Coast (NY2)', 'cloudprovider.paperspace.region.ny2')
        ];

        $this->kindsToRegionsToImages = [
            RemoteDesktopKind::GAMING_PRO_PAPERSPACE => [
                'East Coast (NY2)'   => $this->getImageByInternalName('t2q0g8n')
            ]
        ];
    }

    /**
     * @return Flavor[]
     */
    public function getFlavors(): array
    {
        return $this->flavors;
    }

    /**
     * @return Image[]
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * @return Region[]
     */
    public function getRegions(): array
    {
        return $this->regions;
    }

    public function getAvailableRegionsForKind(RemoteDesktopKind $remoteDesktopKind) : array
    {
        $result = [];
        foreach ($this->kindsToRegionsToImages as $kind => $regionsToImages) {
            if ($kind === $remoteDesktopKind->getIdentifier()) {
                foreach ($regionsToImages as $region => $image) {
                    $result[] = $this->getRegionByInternalName($region);
                }
            }
        }
        return $result;
    }

    public function createInstanceForRemoteDesktopAndRegion(RemoteDesktop $remoteDesktop, Region $region) : CloudInstance
    {
        $instance = new PaperspaceCloudInstance();

        // We use this indirection because it ensures we work with a valid flavor
        $instance->setFlavor($this->getFlavorByInternalName($remoteDesktop->getKind()->getFlavor()->getInternalName()));

        if (array_key_exists($remoteDesktop->getKind()->getIdentifier(), $this->kindsToRegionsToImages)) {
            if (array_key_exists($region->getInternalName(), $this->kindsToRegionsToImages[$remoteDesktop->getKind()->getIdentifier()])) {
                $instance->setImage(
                    $this->kindsToRegionsToImages[$remoteDesktop->getKind()->getIdentifier()][$region->getInternalName()]
                );
            } else {
                throw new \Exception('Cannot match region ' . $region->getInternalName() . ' to an image.');
            }
        } else {
            throw new \Exception('Cannot match kind ' . get_class($remoteDesktop->getKind()) . ' to an image.');
        }

        $instance->setRootVolumeSize(100);
        $instance->setAdditionalVolumeSize(0);

        // We use this indirection because it ensures we work with a valid region
        $instance->setRegion($this->getRegionByInternalName($region->getInternalName()));

        return $instance;
    }

    /**
     * @throws \Exception
     */
    public function getUsageCostsForFlavorImageRegionCombinationForOneInterval(Flavor $flavor, Image $image, Region $region) : float
    {
        return $this->getMaximumUsageCostsForKindForOneInterval($flavor);
    }

    public function getMaximumUsageCostsForKindForOneInterval(RemoteDesktopKind $kind) : float
    {
        if ($kind->getFlavor()->getInternalName() === 'Air') {
            return 0.07;
        }

        if ($kind->getFlavor()->getInternalName() === 'GPU+') {
            return 0.60;
        }

        throw new \Exception('Unknown flavor ' . $kind->getFlavor()->getInternalName());
    }

    public function getMaximumProvisioningCostsForKindForOneInterval(RemoteDesktopKind $kind) : float
    {
        return $this->getProvisioningCostsForFlavorImageRegionVolumeSizesCombinationForOneInterval(
            $kind->getFlavor(), $this->images[0], $this->regions[0], 100, 0
        );
    }

    public function getProvisioningCostsForFlavorImageRegionVolumeSizesCombinationForOneInterval(
        Flavor $flavor, Image $image, Region $region, int $rootVolumeSize, int $additionalVolumeSize) : float
    {
        switch ($rootVolumeSize) {
            case 50:
                return 5.0;
                break;
            case 100:
                return 6.0;
                break;
            case 250:
                return 7.0;
                break;
            case 500:
                return 10.0;
                break;
            case 1000:
                return 20.0;
                break;
            case 2000:
                return 40.0;
                break;
            default:
                throw new \Exception('Cannot calculate monthly Paperspace storage price for volume size ' . $rootVolumeSize);
        }
    }

    public function hasLatencycheckEndpoints() : bool
    {
        return false;
    }

}
