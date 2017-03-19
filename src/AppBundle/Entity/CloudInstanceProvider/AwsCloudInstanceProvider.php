<?php

namespace AppBundle\Entity\CloudInstanceProvider;

use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstanceInterface;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Flavor;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Image;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Entity\RemoteDesktop\RemoteDesktopGamingKind;

class AwsCloudInstanceProvider extends CloudInstanceProvider
{
    /**
     * @return Region[]
     */
    public function getRegions(): array
    {
        return [
            new Region($this, 'eu-west-1', 'cloudprovider.aws.region.eu-west-1'),
            new Region($this, 'eu-central-1', 'cloudprovider.aws.region.eu-central-1'),
            new Region($this, 'us-east-1', 'cloudprovider.aws.region.us-east-1')
        ];
    }

    public function createInstanceForRemoteDesktopAndRegion(RemoteDesktop $remoteDesktop, Region $region) : CloudInstanceInterface
    {
        if ($remoteDesktop->getKind() instanceof RemoteDesktopGamingKind) {
            $instance = new AwsCloudInstance();
            $instance->setFlavor(new Flavor($this, 'g2.2xlarge', ''));

            switch ($region->getInternalName()) {
                case 'eu-west-1':
                    $ami = 'ami-efc87b9c';
                    break;
                case 'eu-central-1':
                    $ami = 'ami-f2fde69e';
                    break;
                case 'us-east-1':
                    $ami = 'ami-b0c7f2da';
                    break;
                default:
                    throw new \Exception('Cannot match region ' . $region->getInternalName() . ' to an AMI.');
            }
            $instance->setImage(new Image($this, $ami, 'Gaming image for ' . $region->getInternalName()));
        } else {
            throw new \Exception('Cannot provide AWS cloud instance for remote desktop kind ' . get_class($remoteDesktop->getKind()));
        }

        return $instance;
    }
}
