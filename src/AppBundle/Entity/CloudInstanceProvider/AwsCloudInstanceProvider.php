<?php

namespace AppBundle\Entity\CloudInstanceProvider;

use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Flavor;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Image;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Entity\RemoteDesktop\RemoteDesktopKind;

class AwsCloudInstanceProvider extends CloudInstanceProvider
{
    protected $flavors = [];
    protected $images = [];
    protected $regions = [];

    protected $kindsToRegionsToImages = [];

    protected $usageCostsInterval = RemoteDesktop::COSTS_INTERVAL_HOURLY;
    protected $provisioningCostsInterval = RemoteDesktop::COSTS_INTERVAL_HOURLY;

    public function __construct()
    {

        // Never remove a flavor, image or region, because there might still be users
        // who have old desktops with this flavor/image/region

        $this->flavors = [
            new Flavor($this, 'g2.2xlarge', '8 vCPUs, 15 GB RAM, 1 GPU'),
            new Flavor($this, 'g2.8xlarge', '32 vCPUs, 60 GB RAM, 4 GPUs'),
            new Flavor($this, 'c4.4xlarge', '16 vCPUs, 30 GB RAM, no GPU')
        ];

        $this->images = [
            new Image($this, 'ami-0e62c361', '[ON HOLD] Gaming for eu-central-1 (Windows Server 2016 v2)'),
            new Image($this, 'ami-8a03a4e5', '[LEGACY]  Gaming for eu-central-1 (Windows Server 2016)'),
            new Image($this, 'ami-14c0107b', '[CURRENT] Gaming for eu-central-1'),
            new Image($this, 'ami-f2fde69e', '[LEGACY]  Gaming for eu-central-1'),

            new Image($this, 'ami-bc4ca8c5', '[ON HOLD] Gaming for eu-west-1 (Windows Server 2016 v2)'),
            new Image($this, 'ami-a9e1f9cf', '[LEGACY]  Gaming for eu-west-1 (Windows Server 2016)'),
            new Image($this, 'ami-a2437cc4', '[CURRENT] Gaming for eu-west-1'),

            new Image($this, 'ami-ed1120fb', '[ON HOLD] Gaming for us-east-1 (Windows Server 2016 v2)'),
            new Image($this, 'ami-a4406eb2', '[LEGACY]  Gaming for us-east-1 (Windows Server 2016)'),
            new Image($this, 'ami-2b4a6b3d', '[LEGACY]  Gaming for us-east-1 (Windows Server 2016)'),
            new Image($this, 'ami-96179a80', '[CURRENT] Gaming for us-east-1'),
            new Image($this, 'ami-10334270', '[LEGACY]  Gaming for us-east-1'),

            new Image($this, 'ami-60b29e00', '[ON HOLD] Gaming for us-west-1 (Windows Server 2016 v2)'),
            new Image($this, 'ami-d3aa87b3', '[LEGACY]  Gaming for us-west-1 (Windows Server 2016)'),
            new Image($this, 'ami-d8c59fb8', '[CURRENT] Gaming for us-west-1'),
            new Image($this, 'ami-b0c7f2da', '[LEGACY]  Gaming for us-west-1'),

            new Image($this, 'ami-c49a89a7', '[ON HOLD] Gaming for ap-southeast-2 (Windows Server 2016 v2)'),
            new Image($this, 'ami-20302043', '[LEGACY]  Gaming for ap-southeast-2 (Windows Server 2016)'),

            new Image($this, 'ami-70c0101f', '[CURRENT] CAD for eu-central-1'),
            new Image($this, 'ami-5c39063a', '[CURRENT] CAD for eu-west-1'),
            new Image($this, 'ami-5c169b4a', '[CURRENT] CAD for us-east-1'),
            new Image($this, 'ami-cec79dae', '[CURRENT] CAD for us-west-1'),

            new Image($this, 'ami-71c0101e', '[CURRENT] 3D Media for eu-central-1'),
            new Image($this, 'ami-ff2a1599', '[CURRENT] 3D Media for eu-west-1'),
            new Image($this, 'ami-7d119c6b', '[CURRENT] 3D Media for us-east-1'),
            new Image($this, 'ami-cfc79daf', '[CURRENT] 3D Media for us-west-1'),

            new Image($this, 'ami-51c2123e', '[CURRENT] Unity for eu-central-1'),
            new Image($this, 'ami-ef3b0489', '[CURRENT] Unity for eu-west-1'),
            new Image($this, 'ami-71119c67', '[CURRENT] Unity for us-east-1'),
            new Image($this, 'ami-92c79df2', '[CURRENT] Unity for us-west-1')
        ];


        $this->regions = [
            new Region($this, 'eu-central-1', 'cloudprovider.aws.region.eu-central-1'),
            new Region($this, 'eu-west-1', 'cloudprovider.aws.region.eu-west-1'),
            new Region($this, 'us-east-1', 'cloudprovider.aws.region.us-east-1'),
            new Region($this, 'us-west-1', 'cloudprovider.aws.region.us-west-1'),
            new Region($this, 'ap-southeast-2', 'cloudprovider.aws.region.ap-southeast-2', false)
        ];

        $this->kindsToRegionsToImages = [
            RemoteDesktopKind::GAMING_PRO => [
                'eu-central-1'   => $this->getImageByInternalName('ami-14c0107b'),
                'eu-west-1'      => $this->getImageByInternalName('ami-a2437cc4'),
                'us-east-1'      => $this->getImageByInternalName('ami-96179a80'),
                'us-west-1'      => $this->getImageByInternalName('ami-d8c59fb8'),
            ],
            RemoteDesktopKind::CAD_PRO => [
                'eu-central-1' => $this->getImageByInternalName('ami-70c0101f'),
                'eu-west-1' => $this->getImageByInternalName('ami-5c39063a'),
                'us-east-1' => $this->getImageByInternalName('ami-5c169b4a'),
                'us-west-1' => $this->getImageByInternalName('ami-cec79dae'),
            ],
            RemoteDesktopKind::CAD_ULTRA => [
                'eu-central-1' => $this->getImageByInternalName('ami-70c0101f'),
                'eu-west-1' => $this->getImageByInternalName('ami-5c39063a'),
                'us-east-1' => $this->getImageByInternalName('ami-5c169b4a'),
                'us-west-1' => $this->getImageByInternalName('ami-cec79dae'),
            ],
            RemoteDesktopKind::THREED_MEDIA_PRO => [
                'eu-central-1' => $this->getImageByInternalName('ami-71c0101e'),
                'eu-west-1' => $this->getImageByInternalName('ami-ff2a1599'),
                'us-east-1' => $this->getImageByInternalName('ami-7d119c6b'),
                'us-west-1' => $this->getImageByInternalName('ami-cfc79daf'),
            ],
            RemoteDesktopKind::THREED_MEDIA_ULTRA => [
                'eu-central-1' => $this->getImageByInternalName('ami-71c0101e'),
                'eu-west-1' => $this->getImageByInternalName('ami-ff2a1599'),
                'us-east-1' => $this->getImageByInternalName('ami-7d119c6b'),
                'us-west-1' => $this->getImageByInternalName('ami-cfc79daf'),
            ],
            RemoteDesktopKind::UNITY_PRO => [
                'eu-central-1' => $this->getImageByInternalName('ami-51c2123e'),
                'eu-west-1' => $this->getImageByInternalName('ami-ef3b0489'),
                'us-east-1' => $this->getImageByInternalName('ami-71119c67'),
                'us-west-1' => $this->getImageByInternalName('ami-92c79df2'),
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
        $instance = new AwsCloudInstance();

        // We use this indirection because it ensures we work with a valid flavor
        $instance->setFlavor($this->getFlavorByInternalName($remoteDesktop->getKind()->getFlavor()->getInternalName()));

        if (array_key_exists($remoteDesktop->getKind()->getIdentifier(), $this->kindsToRegionsToImages)) {
            if (array_key_exists($region->getInternalName(), $this->kindsToRegionsToImages[$remoteDesktop->getKind()->getIdentifier()])) {
                $instance->setImage(
                    $this->kindsToRegionsToImages[$remoteDesktop->getKind()->getIdentifier()][$region->getInternalName()]
                );
            } else {
                throw new \Exception('Cannot match region ' . $region->getInternalName() . ' to an AMI.');
            }
        } else {
            throw new \Exception('Cannot match kind ' . get_class($remoteDesktop->getKind()) . ' to an AMI.');
        }

        $instance->setRootVolumeSize($remoteDesktop->getKind()->getRootVolumeSize());
        $instance->setAdditionalVolumeSize($remoteDesktop->getKind()->getAdditionalVolumeSize());

        // We use this indirection because it ensures we work with a valid region
        $instance->setRegion($this->getRegionByInternalName($region->getInternalName()));

        return $instance;
    }

    /**
     * @throws \Exception
     */
    public function getUsageCostsForKindImageRegionCombinationForOneInterval(RemoteDesktopKind $kind, Image $image, Region $region) : float
    {
        return $this->getMaximumUsageCostsForKindForOneInterval($kind);
    }

    public function getMaximumUsageCostsForKindForOneInterval(RemoteDesktopKind $kind) : float
    {
        if ($kind->getFlavor()->getInternalName() === 'g2.2xlarge') {
            return 1.95;
        }

        if ($kind->getFlavor()->getInternalName() === 'c4.4xlarge') {
            return 1.95;
        }

        if ($kind->getFlavor()->getInternalName() === 'g2.8xlarge') {
            return 4.95;
        }

        throw new \Exception('Unknown flavor ' . $kind->getFlavor()->getInternalName());
    }

    public function getMaximumProvisioningCostsForKindForOneInterval(RemoteDesktopKind $kind) : float
    {
        if ($kind->getFlavor()->getInternalName() === 'g2.2xlarge') {
            $rootVolumeSize = 60;
            $additionalVolumeSize = 200;
        } elseif ($kind->getFlavor()->getInternalName() === 'g2.8xlarge') {
            $rootVolumeSize = 240;
            $additionalVolumeSize = 0;
        } elseif ($kind->getFlavor()->getInternalName() === 'c4.4xlarge') {
            $rootVolumeSize = 60;
            $additionalVolumeSize = 200;
        } else {
            throw new \Exception('Missing volume size mapping for flavor ' . $kind->getFlavor()->getInternalName());
        }

        return $this->getProvisioningCostsForFlavorImageRegionVolumeSizesCombinationForOneInterval(
            $kind->getFlavor(), $this->images[0], $this->regions[0], $rootVolumeSize, $additionalVolumeSize
        );
    }

    public function getProvisioningCostsForFlavorImageRegionVolumeSizesCombinationForOneInterval(
        Flavor $flavor, Image $image, Region $region, int $rootVolumeSize, int $additionalVolumeSize) : float
    {
        $pricePerGBPerMonth = 0.119; // gp2 Volume type
        $daysPerMonth = 30;
        $hoursPerDay = 24;
        $hoursPerMonth = $daysPerMonth * $hoursPerDay;
        return round(( ($rootVolumeSize + $additionalVolumeSize) * $pricePerGBPerMonth ) / $hoursPerMonth, 2);
    }

    public function hasLatencycheckEndpoints() : bool
    {
        return true;
    }

}
