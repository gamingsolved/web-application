<?php

namespace AppBundle\Entity\RemoteDesktop;

use AppBundle\Entity\CloudInstanceProvider\AwsCloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Flavor;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Image;

class RemoteDesktopUnityProKind extends RemoteDesktopKind {

    public function getIdentifier() : int
    {
        return RemoteDesktopKind::UNITY_PRO;
    }

    public function __toString(): string
    {
        return 'remoteDesktop.kind.unitypro';
    }

    public function getCloudInstanceProvider() : CloudInstanceProvider
    {
        return new AwsCloudInstanceProvider();
    }

    public function getFlavor(): Flavor {
        return $this->getCloudInstanceProvider()->getFlavorByInternalName('c4.4xlarge');
    }

}
