<?php

namespace AppBundle\Entity\RemoteDesktop;

use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Flavor;

interface RemoteDesktopKindInterface
{
    public function __toString() : string;
    public function getName() : string;
    public function getCloudInstanceProvider() : CloudInstanceProvider;
    public function getIdentifier() : int;
    public function getFlavor(): Flavor;
    public function getMaximumHourlyCosts(): float;
}

// Never remove kinds, only add new - existing customers might have old desktops with existing kinds!

abstract class RemoteDesktopKind implements RemoteDesktopKindInterface
{
    const GAMING_PRO = 0;
    const CAD_PRO = 1;
    const CAD_ULTRA = 2;
    const THREED_MEDIA_PRO = 3;
    const THREED_MEDIA_ULTRA = 4;

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

        if ($kind === self::THREED_MEDIA_ULTRA) {
            return new RemoteDesktop3dMediaUltraKind();
        }

        throw new \Exception('Unknown remote desktop kind ' . $kind);
    }

    public static function getAvailableKinds() : array
    {
        return [
            self::createRemoteDesktopKind(self::GAMING_PRO),
            self::createRemoteDesktopKind(self::CAD_PRO),
            self::createRemoteDesktopKind(self::CAD_ULTRA),
            self::createRemoteDesktopKind(self::THREED_MEDIA_PRO),
            self::createRemoteDesktopKind(self::THREED_MEDIA_ULTRA)
        ];
    }

    public function getName() : string
    {
        return (string)$this;
    }

    public function getMaximumHourlyCosts() : float
    {
        return $this->getCloudInstanceProvider()->getMaximumHourlyCostsForFlavor($this->getFlavor());
    }
}
