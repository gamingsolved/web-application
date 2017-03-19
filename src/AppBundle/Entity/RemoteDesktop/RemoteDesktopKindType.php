<?php

namespace AppBundle\Entity\RemoteDesktop;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class RemoteDesktopKindType extends Type
{
    public function getName()
    {
        return 'RemoteDesktopKindType';
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'SMALLINT';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return RemoteDesktopKind::createRemoteDesktopKind((int)$value);
    }

    public function convertToDatabaseValue($valueObject, AbstractPlatform $platform)
    {
        if ($valueObject instanceof RemoteDesktopGamingKind) {
            return $value = RemoteDesktopKind::GAMING;
        }

        if ($valueObject instanceof RemoteDesktopCadKind) {
            return $value = RemoteDesktopKind::CAD;
        }

        throw new \Exception('Could not convert the RemoteDesktopKind object of class ' . get_class($valueObject) . ' to a known RemoteDesktopKind value');
    }
}
