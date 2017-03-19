<?php

namespace AppBundle\Coordinator\CloudInstance;

use AppBundle\Entity\CloudInstance\CloudInstance;

class AwsCloudInstanceCoordinator
{
    public static function launchCloudInstance(CloudInstance $cloudInstance) : bool
    {
        return true;
    }
}
