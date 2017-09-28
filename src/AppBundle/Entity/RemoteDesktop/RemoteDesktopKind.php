<?php

namespace AppBundle\Entity\RemoteDesktop;

use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Flavor;
use AppBundle\Entity\User;

interface RemoteDesktopKindInterface
{
    public function __toString() : string;
    public static function getAvailableKinds(User $user) : array;
    public function getName() : string;
    public function getCloudInstanceProvider() : CloudInstanceProvider;
    public function getIdentifier() : int;
    public function getFlavor(): Flavor;
    public function getMaximumUsageCostsForOneInterval(): float;
    public function getMaximumProvisioningCostsForOneInterval(): float;
    public function getMouseRelativeValue(): string;
    public function getRootVolumeSize(): int;
    public function getAdditionalVolumeSize(): int;
}

/**
 * Class RemoteDesktopKind
 *
 * A remote desktop kind is a high level abstraction which allows to get from a functional choice by the user - what
 * type of desktop do I want to create - to the low-level cloud computing details (what kind of machine at which cloud
 * provider provides the requested kind of desktop?).
 *
 * It also encapsulates or maps to low-level cloud provider details like "in which regions can this kind of remote
 * desktop be provisioned?" or "how much does a usage hour for this kind of remote desktop cost?"
 *
 * This is in contrast to cloud provider specific stuff like image names etc., which are encapsulated in
 * {@see CloudInstanceProvider} subclasses.
 */
abstract class RemoteDesktopKind implements RemoteDesktopKindInterface
{

    // Never remove kinds, only add new - existing customers might have old desktops with existing kinds!

    const GAMING_PRO = 0;
    const CAD_PRO = 1;
    const CAD_ULTRA = 2;
    const THREED_MEDIA_PRO = 3;
    const THREED_MEDIA_ULTRA = 4;
    const UNITY_PRO = 5;
    const GAMING_PRO_PAPERSPACE = 6;

    protected $rootVolumeSize = null;
    protected $additionalVolumeSize = null;

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

    public static function getAvailableKinds(User $user) : array
    {
        if (in_array('ROLE_BETATESTER_LEVEL_0', $user->getRoles())) {
            return [
                self::createRemoteDesktopKind(self::GAMING_PRO),
                self::createRemoteDesktopKind(self::GAMING_PRO_PAPERSPACE)
            ];
        } else {
            return [
                self::createRemoteDesktopKind(self::GAMING_PRO)
            ];
        }
    }

    public function getName() : string
    {
        return (string)$this;
    }

    public function getMaximumUsageCostsForOneInterval() : float
    {
        return $this->getCloudInstanceProvider()->getMaximumUsageCostsForKindForOneInterval($this);
    }

    public function getMaximumProvisioningCostsForOneInterval(): float
    {
        return $this->getCloudInstanceProvider()->getMaximumProvisioningCostsForKindForOneInterval($this);
    }

    public function getMouseRelativeValue(): string
    {
        return 'false';
    }

    public function getAvailableRegions() : array
    {
        return $this->getCloudInstanceProvider()->getAvailableRegionsForKind($this);
    }

    public function getRootVolumeSize(): int
    {
        if (!isset($this->rootVolumeSize)) {
            throw new \Exception('Root volume size for kind ' . $this->getName() . ' is not set.');
        } else {
            return $this->rootVolumeSize;
        }
    }

    public function getAdditionalVolumeSize(): int
    {
        if (!is_int($this->rootVolumeSize)) {
            throw new \Exception('Additional volume size for kind ' . $this->getName() . ' is not set.');
        } else {
            return $this->additionalVolumeSize;
        }
    }

}
