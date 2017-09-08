<?php

namespace AppBundle\Entity\RemoteDesktop;

use AppBundle\Entity\CloudInstanceProvider\AwsCloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Flavor;

class RemoteDesktopCadUltraKind extends RemoteDesktopKind
{
    protected $rootVolumeSize = 240;
    protected $additionalVolumeSize = 0;

    public function getIdentifier() : int
    {
        return RemoteDesktopKind::CAD_ULTRA;
    }

    public function __toString(): string
    {
        return 'remoteDesktop.kind.cadultra';
    }

    public function getCloudInstanceProvider() : CloudInstanceProvider
    {
        return new AwsCloudInstanceProvider();
    }

    public function getFlavor(): Flavor {
        return $this->getCloudInstanceProvider()->getFlavorByInternalName('g2.8xlarge');
    }
}
