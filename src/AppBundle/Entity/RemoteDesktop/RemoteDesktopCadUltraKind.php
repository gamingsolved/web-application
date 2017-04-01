<?php

namespace AppBundle\Entity\RemoteDesktop;

use AppBundle\Entity\CloudInstanceProvider\AwsCloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProvider;

class RemoteDesktopCadUltraKind extends RemoteDesktopKind {

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
}
