<?php

namespace AppBundle\Entity\CloudInstanceProvider;

use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstanceInterface;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Flavor;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Image;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Entity\RemoteDesktop\RemoteDesktopGamingKind;
use AppBundle\Entity\RemoteDesktop\RemoteDesktopCadKind;

class AwsCloudInstanceProvider extends CloudInstanceProvider
{
    protected $flavors;
    protected $images;
    protected $regions;

    public function __construct()
    {
        $this->flavors = [
            new Flavor($this, 'g2.2xlarge', '8 CPUs, 15 GB RAM, 1 GPU')
        ];

        $this->images = [
            new Image($this, 'ami-efc87b9c', 'AMI efc87b9c'),
            new Image($this, 'ami-f2fde69e', 'AMI f2fde69e'),
            new Image($this, 'ami-b0c7f2da', 'AMI b0c7f2da'),
            new Image($this, 'ami-10334270', 'Scalable Graphics Windows Server 2012, us-west-1')
        ];

        $this->regions = [
            new Region($this, 'eu-west-1', 'cloudprovider.aws.region.eu-west-1'),
            new Region($this, 'eu-central-1', 'cloudprovider.aws.region.eu-central-1'),
            new Region($this, 'us-east-1', 'cloudprovider.aws.region.us-east-1'),
            new Region($this, 'us-west-1', 'cloudprovider.aws.region.us-west-1')
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
        if (   $remoteDesktop->getKind() instanceof RemoteDesktopGamingKind
            || $remoteDesktop->getKind() instanceof RemoteDesktopCadKind
        ) {

            $instance = new AwsCloudInstance();
            $instance->setFlavor($this->getFlavorByInternalName('g2.2xlarge'));

            $amiInternalName = '';
            switch ($region->getInternalName()) {
                case 'eu-west-1':
                    $amiInternalName = 'ami-efc87b9c';
                    break;
                case 'eu-central-1':
                    $amiInternalName = 'ami-f2fde69e';
                    break;
                case 'us-east-1':
                    $amiInternalName = 'ami-b0c7f2da';
                    break;
                case 'us-west-1':
                    $amiInternalName = 'ami-10334270';
                    break;
                default:
                    throw new \Exception('Cannot match region ' . $region->getInternalName() . ' to an AMI.');
            }
            $instance->setImage($this->getImageByInternalName($amiInternalName));

            // We use this indirection because it ensures we get only persist a valid region
            $instance->setRegion($this->getRegionByInternalName($region->getInternalName()));
        } else {
            throw new \Exception('Cannot provide AWS cloud instance for remote desktop kind ' . get_class($remoteDesktop->getKind()));
        }

        return $instance;
    }

    /**
     * @throws \Exception
     */
    public function getHourlyCostsForFlavorImageRegionCombination(Flavor $flavor, Image $image, Region $region) : float
    {
        if ($flavor->getInternalName() === 'g2.2xlarge') {
            return '1.99';
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
