<?php

namespace AppBundle\Entity\RemoteDesktop;

use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProvider;

interface RemoteDesktopKindInterface
{
    public function __toString() : string;
    public function getCloudInstanceProvider() : CloudInstanceProvider;
}

abstract class RemoteDesktopKind implements RemoteDesktopKindInterface
{
    const GAMING = 0;
    const CAD = 1;

    /**
     * @throws \Exception
     */
    public static function createRemoteDesktopKind(int $kind) : RemoteDesktopKindInterface
    {
        if ($kind === self::GAMING) {
            return new RemoteDesktopGamingKind();
        } else {
            throw new \Exception();
        }
    }
}
