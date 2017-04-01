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

    /**
     * @param RemoteDesktopKind $valueObject
     * @throws \Exception
     */
    public function convertToDatabaseValue($valueObject, AbstractPlatform $platform) : int
    {
        return $valueObject->getIdentifier();
    }
}
