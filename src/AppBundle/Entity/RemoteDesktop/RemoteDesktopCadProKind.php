<?php

namespace AppBundle\Entity\RemoteDesktop;

use AppBundle\Entity\CloudInstanceProvider\AwsCloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProvider;

class RemoteDesktopCadProKind extends RemoteDesktopKind {

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
}
