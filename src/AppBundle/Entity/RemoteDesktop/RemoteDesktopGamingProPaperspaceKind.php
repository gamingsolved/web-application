<?php

namespace AppBundle\Entity\RemoteDesktop;

use AppBundle\Entity\CloudInstanceProvider\AwsCloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\PaperspaceCloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Flavor;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Image;

class RemoteDesktopGamingProPaperspaceKind extends RemoteDesktopKind {

    public function getIdentifier() : int
    {
        return RemoteDesktopKind::GAMING_PRO_PAPERSPACE;
    }

    public function __toString(): string
    {
        return 'remoteDesktop.kind.gamingpropaperspace';
    }

    public function getCloudInstanceProvider() : CloudInstanceProvider
    {
        return new PaperspaceCloudInstanceProvider();
    }

    public function getFlavor(): Flavor {
        return $this->getCloudInstanceProvider()->getFlavorByInternalName('Air');
    }

    // Absolute mode makes the mouse unusable in game
    public function getMouseRelativeValue(): string
    {
        return 'true';
    }
}
