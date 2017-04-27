<?php

namespace AppBundle\Entity\RemoteDesktop;

use AppBundle\Entity\CloudInstanceProvider\AwsCloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Flavor;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Image;

class RemoteDesktopGamingProKind extends RemoteDesktopKind {

    public function getIdentifier() : int
    {
        return RemoteDesktopKind::GAMING_PRO;
    }

    public function __toString(): string
    {
        return 'remoteDesktop.kind.gamingpro';
    }

    public function getCloudInstanceProvider() : CloudInstanceProvider
    {
        return new AwsCloudInstanceProvider();
    }

    public function getFlavor(): Flavor {
        return $this->getCloudInstanceProvider()->getFlavorByInternalName('g2.2xlarge');
    }

    // Absolute mode makes the mouse unusable in game
    public function getMouseRelativeValue(): string
    {
        return 'true';
    }
}
