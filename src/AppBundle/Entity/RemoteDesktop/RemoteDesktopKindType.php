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
        return 'INT';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === RemoteDesktopKind::GAMING) {
            return new RemoteDesktopKindGaming();
        } else {
            throw new \Exception('Could not convert the RemoteDesktopKind value ' . $value . ' to a known RemoteDesktopKind object');
        }
    }

    public function convertToDatabaseValue($valueObject, AbstractPlatform $platform)
    {
        if ($valueObject instanceof RemoteDesktopKindGaming) {
            $value = RemoteDesktopKind::GAMING;
        } else {
            throw new \Exception('Could not convert the RemoteDesktopKind object of class ' . get_class($valueObject) . ' to a known RemoteDesktopKind value');
        }

        return $value;
    }
}
