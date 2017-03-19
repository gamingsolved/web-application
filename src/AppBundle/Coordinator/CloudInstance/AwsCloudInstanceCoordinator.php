<?php

namespace AppBundle\Coordinator\CloudInstance;

use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Utility\Cryptor;

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

    public static function tryRetrievingAdminPassword(CloudInstance $cloudInstance, string $encryptionKey) : string
    {
        $cryptor = new Cryptor();
        return $cryptor->encryptString(
            'secret password',
            $encryptionKey
        );
    }
}
