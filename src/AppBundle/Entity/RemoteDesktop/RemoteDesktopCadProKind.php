<?php

namespace AppBundle\Entity\RemoteDesktop;

use AppBundle\Entity\CloudInstanceProvider\AwsCloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Flavor;

class RemoteDesktopCadProKind extends RemoteDesktopKind
{
    protected $rootVolumeSize = 60;
    protected $additionalVolumeSize = 200;

    public function getIdentifier() : int
    {
        return RemoteDesktopKind::CAD_PRO;
    }

    public function __toString(): string
    {
        return 'remoteDesktop.kind.cadpro';
    }

    public function getCloudInstanceProvider() : CloudInstanceProvider
    {
        return new AwsCloudInstanceProvider();
    }

    public function getFlavor(): Flavor {
        return $this->getCloudInstanceProvider()->getFlavorByInternalName('g2.2xlarge');
    }
}
