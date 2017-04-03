<?php

namespace AppBundle\Entity\RemoteDesktop;

use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProvider;

interface RemoteDesktopKindInterface
{
    public function __toString() : string;
    public function getCloudInstanceProvider() : CloudInstanceProvider;
    public function getIdentifier() : int;
}

abstract class RemoteDesktopKind implements RemoteDesktopKindInterface
{
    const GAMING_PRO = 0;
    const CAD_PRO = 1;
    const CAD_ULTRA = 2;
    const THREED_MEDIA_PRO = 3;

    /**
     * @throws \Exception
     */
    public static function createRemoteDesktopKind(int $kind) : RemoteDesktopKindInterface
    {
        if ($kind === self::GAMING_PRO) {
            return new RemoteDesktopGamingProKind();
        }

        if ($kind === self::CAD_PRO) {
            return new RemoteDesktopCadProKind();
        }

        if ($kind === self::CAD_ULTRA) {
            return new RemoteDesktopCadUltraKind();
        }

        if ($kind === self::THREED_MEDIA_PRO) {
            return new RemoteDesktop3dMediaProKind();
        }

        throw new \Exception('Unknown remote desktop kind ' . $kind);
    }
}
