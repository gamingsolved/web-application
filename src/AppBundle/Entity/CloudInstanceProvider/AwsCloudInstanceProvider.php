<?php

namespace AppBundle\Entity\CloudInstanceProvider;

class AwsCloudInstanceProvider extends CloudInstanceProvider
{
    public function getRegions() : array
    {
        return [
            new Region($this, 'eu-central-1', 'Frankfurt, Germany'),
            new Region($this, 'us-east-1', 'North Virginia, US East'),
        ];
    }

    public function getFlavors() : array
    {
        return [
            new Flavor($this, 'g2.2xlarge', '1 GPU, 8 CPUs, 15 GB Memory, 60 GB SSD'),
            new Flavor($this, 'g2.8xlarge', '4 GPUs, 32 CPUs, 60 GB Memory, 2x120 GB SSD')
        ];
    }

    public function getImages() : array
    {
        return [
            new Image($this, 'ami-5d60b732', 'Gaming Image with Steam and UPlay preinstalled')
        ];
    }
}
