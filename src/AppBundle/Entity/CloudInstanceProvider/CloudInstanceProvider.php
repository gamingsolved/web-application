<?php

namespace AppBundle\Entity\CloudInstanceProvider;

interface CloudInstanceProviderInterface
{
    public function getRegions() : array;

    public function getFlavors() : array;

    public function getImages() : array;
}

abstract class CloudInstanceProvider implements CloudInstanceProviderInterface
{
    const CLOUD_INSTANCE_PROVIDER_AWS_ID = 0;
}
