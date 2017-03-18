<?php

namespace AppBundle\Entity\CloudInstanceProvider;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class CloudInstanceProviderType extends Type
{
    public function getName()
    {
        return 'CloudInstanceProviderType';
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'INT';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ((int)$value === CloudInstanceProvider::AWS) {
            return new AwsCloudInstanceProvider();
        } else {
            throw new \Exception('Could not convert the CloudInstanceProvider value ' . $value . ' to a known CloudInstanceProvider object');
        }
    }

    public function convertToDatabaseValue($valueObject, AbstractPlatform $platform)
    {
        if ($valueObject instanceof AwsCloudInstanceProvider) {
            $value = CloudInstanceProvider::AWS;
        } else {
            throw new \Exception('Could not convert the CloudInstanceProvider object of class ' . get_class($valueObject) . ' to a known CloudInstanceProvider value');
        }

        return $value;
    }
}
