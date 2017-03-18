<?php

namespace AppBundle\Entity\RemoteDesktop;

use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProvider;

interface RemoteDesktopKindInterface
{
    public function getCloudInstanceProvider() : CloudInstanceProvider;
}

abstract class RemoteDesktopKind implements RemoteDesktopKindInterface
{
    const GAMING = 0;
    const CAD = 1;
}
