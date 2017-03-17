<?php

namespace AppBundle\Entity\CloudInstance;

abstract class CloudInstance
{
    const CLOUD_INSTANCE_PROVIDER_AWS = 0;

    public function getCloudProvider()
    {
        if (!defined(self::CLOUD_INSTANCE_PROVIDER)) {
            throw new \Exception();
        }
    }
}
