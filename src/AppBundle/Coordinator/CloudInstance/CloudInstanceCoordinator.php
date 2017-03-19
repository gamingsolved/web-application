<?php

namespace AppBundle\Coordinator\CloudInstance;

use AppBundle\Entity\CloudInstance\CloudInstance;

interface CloudInstanceCoordinator
{
    public static function launch(CloudInstance $cloudInstance) : bool;

    public static function hasFinishedLaunching(CloudInstance $cloudInstance) : bool;

    public static function tryRetrievingAdminPassword(CloudInstance $cloudInstance) : string;
}
