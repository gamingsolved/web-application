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
        return 'SMALLINT';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ((int)$value === CloudInstanceProvider::PROVIDER_AWS) {
            return new AwsCloudInstanceProvider();
        } elseif ((int)$value === CloudInstanceProvider::PROVIDER_PAPERSPACE) {
            return new PaperspaceCloudInstanceProvider();
        } else {
            throw new \Exception('Could not convert the CloudInstanceProvider value ' . $value . ' to a known CloudInstanceProvider object');
        }
    }

    public function convertToDatabaseValue($valueObject, AbstractPlatform $platform)
    {
        if ($valueObject instanceof AwsCloudInstanceProvider) {
            $value = CloudInstanceProvider::PROVIDER_AWS;
        } elseif ($valueObject instanceof PaperspaceCloudInstanceProvider) {
            $value = CloudInstanceProvider::PROVIDER_PAPERSPACE;
        } else {
            throw new \Exception('Could not convert the CloudInstanceProvider object of class ' . get_class($valueObject) . ' to a known CloudInstanceProvider value');
        }

        return $value;
    }
}
