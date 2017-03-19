<?php

namespace AppBundle\Coordinator\CloudInstance;

use AppBundle\Entity\CloudInstance\CloudInstance;

class AwsCloudInstanceCoordinator implements CloudInstanceCoordinator
{
    public static function launch(CloudInstance $cloudInstance) : bool
    {
        return true;
    }

    public static function hasFinishedLaunching(CloudInstance $cloudInstance) : bool
    {
        return true;
    }

    public static function tryRetrievingAdminPassword(CloudInstance $cloudInstance) : string
    {
        return 'secretAdminPwd';
    }
}
