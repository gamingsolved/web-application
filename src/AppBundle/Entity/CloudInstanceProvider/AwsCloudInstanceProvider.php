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

    protected $kindToFlavor = [];
    protected $kindToRegionToImage = [];

    public function __construct()
    {

        // Never remove remove a flavor, image or region, because there might still be users
        // who have old desktops with this flavor/image/region

        $flavorG22xlarge = new Flavor($this, 'g2.2xlarge', '8 vCPUs, 15 GB RAM, 1 GPU');
        $flavorG28xlarge = new Flavor($this, 'g2.8xlarge', '32 vCPUs, 60 GB RAM, 4 GPUs');

        $this->flavors = [
            $flavorG22xlarge,
            $flavorG28xlarge
        ];

        $this->images = [
            new Image($this, 'ami-14c0107b', '[CURRENT] Gaming for eu-central-1'),
            new Image($this, 'ami-a2437cc4', '[CURRENT] Gaming for eu-west-1'),
            new Image($this, 'ami-70c0101f', '[CURRENT] CAD for eu-central-1'),
            new Image($this, 'ami-5c39063a', '[CURRENT] CAD for eu-west-1'),
            new Image($this, 'ami-f2fde69e', '[LEGACY] Gaming for eu-central-1'),
            new Image($this, 'ami-10334270', '[LEGACY] Gaming for us-east-1'),
            new Image($this, 'ami-b0c7f2da', '[LEGACY] Gaming for us-west-1')
        ];


        $this->regions = [
            new Region($this, 'eu-central-1', 'cloudprovider.aws.region.eu-central-1'),
            new Region($this, 'eu-west-1', 'cloudprovider.aws.region.eu-west-1'),
        ];

        $this->kindToFlavor = [
            RemoteDesktopKind::GAMING_PRO => $flavorG22xlarge,
            RemoteDesktopKind::CAD_PRO => $flavorG22xlarge,
            RemoteDesktopKind::CAD_ULTRA => $flavorG28xlarge
        ];

        $this->kindToRegionToImage = [
            RemoteDesktopKind::GAMING_PRO => [
                'eu-central-1' => $this->getImageByInternalName('ami-14c0107b'),
                'eu-west-1' => $this->getImageByInternalName('ami-a2437cc4'),
            ],
            RemoteDesktopKind::CAD_PRO => [
                'eu-central-1' => $this->getImageByInternalName('ami-70c0101f'),
                'eu-west-1' => $this->getImageByInternalName('ami-5c39063a'),
            ],
            RemoteDesktopKind::CAD_ULTRA => [
                'eu-central-1' => $this->getImageByInternalName('ami-70c0101f'),
                'eu-west-1' => $this->getImageByInternalName('ami-5c39063a'),
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

    public function createInstanceForRemoteDesktopAndRegion(RemoteDesktop $remoteDesktop, Region $region) : CloudInstance
    {
        $instance = new AwsCloudInstance();
        if (array_key_exists($remoteDesktop->getKind()->getIdentifier(), $this->kindToFlavor)) {
            $instance->setFlavor($this->kindToFlavor[$remoteDesktop->getKind()->getIdentifier()]);
        }

        if (array_key_exists($remoteDesktop->getKind()->getIdentifier(), $this->kindToRegionToImage)) {
            if (array_key_exists($region->getInternalName(), $this->kindToRegionToImage[$remoteDesktop->getKind()->getIdentifier()])) {
                $instance->setImage(
                    $this->kindToRegionToImage[$remoteDesktop->getKind()->getIdentifier()][$region->getInternalName()]
                );
            } else {
                throw new \Exception('Cannot match region ' . $region->getInternalName() . ' to an AMI.');
            }
        } else {
            throw new \Exception('Cannot match kind ' . get_class($remoteDesktop->getKind()) . ' to an AMI.');
        }

        // We use this indirection because it ensures we get only persist a valid region
        $instance->setRegion($this->getRegionByInternalName($region->getInternalName()));

        return $instance;
    }

    /**
     * @throws \Exception
     */
    public function getHourlyCostsForFlavorImageRegionCombination(Flavor $flavor, Image $image, Region $region) : float
    {
        if ($flavor->getInternalName() === 'g2.2xlarge') {
            return '1.99';
        } elseif($flavor->getInternalName() === 'g2.8xlarge') {
            return '5.99';
        } else {
            throw new \Exception(
                'Could not get hourly costs for flavor '
                . $flavor->getInternalName()
                . ', image '
                . $image->getInternalName()
                . ', region'
                . $region->getInternalName()
            );
        }
    }
}
