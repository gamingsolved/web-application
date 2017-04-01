<?php

namespace AppBundle\Entity\RemoteDesktop;

use AppBundle\Entity\CloudInstanceProvider\AwsCloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProvider;

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
}
