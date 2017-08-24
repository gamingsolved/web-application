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
    public function getMaximumUsageCostsForOneInterval(): float;
    public function getMouseRelativeValue(): string;
}

// Never remove kinds, only add new - existing customers might have old desktops with existing kinds!

abstract class RemoteDesktopKind implements RemoteDesktopKindInterface
{
    const GAMING_PRO = 0;
    const CAD_PRO = 1;
    const CAD_ULTRA = 2;
    const THREED_MEDIA_PRO = 3;
    const THREED_MEDIA_ULTRA = 4;
    const UNITY_PRO = 5;
    const GAMING_PRO_PAPERSPACE = 6;

    /**
     * @throws \Exception
     */
    public static function createRemoteDesktopKind(int $kind) : RemoteDesktopKind
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

        if ($kind === self::UNITY_PRO) {
            return new RemoteDesktopUnityProKind();
        }

        if ($kind === self::GAMING_PRO_PAPERSPACE) {
            return new RemoteDesktopGamingProPaperspaceKind();
        }

        throw new \Exception('Unknown remote desktop kind ' . $kind);
    }

    public static function getAvailableKinds() : array
    {
        return [
            self::createRemoteDesktopKind(self::GAMING_PRO),
            self::createRemoteDesktopKind(self::GAMING_PRO_PAPERSPACE)
        ];
    }

    public function getName() : string
    {
        return (string)$this;
    }

    public function getMaximumUsageCostsForOneInterval() : float
    {
        return $this->getCloudInstanceProvider()->getMaximumUsageCostsForFlavorForOneInterval($this->getFlavor());
    }

    public function getMouseRelativeValue(): string
    {
        return 'false';
    }
}
