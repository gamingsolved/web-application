<?php

namespace AppBundle\Coordinator\CloudInstance;

use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use Symfony\Component\Console\Output\OutputInterface;

interface CloudInstanceCoordinator
{
    public function __construct(array $credentials, Region $region, OutputInterface $output);

    public function triggerLaunchOfCloudInstance(CloudInstance $cloudInstance) : void;

    public function updateCloudInstanceWithProviderSpecificInfoAfterLaunchWasTriggered(CloudInstance $cloudInstance) : void;

    public function triggerStartOfCloudInstance(CloudInstance $cloudInstance) : void;

    public function cloudInstanceIsRunning(CloudInstance $cloudInstance) : bool;

    public function getPublicAddressOfRunningCloudInstance(CloudInstance $cloudInstance) : string;

    public function getAdminPasswordOfRunningCloudInstance(CloudInstance $cloudInstance) : string;

    public function triggerStopOfCloudInstance(CloudInstance $cloudInstance) : void;

    public function cloudInstanceIsStopped(CloudInstance $cloudInstance) : bool;

    public function triggerTerminationOfCloudInstance(CloudInstance $cloudInstance) : void;

    public function cloudInstanceIsTerminated(CloudInstance $cloudInstance) : bool;
}
