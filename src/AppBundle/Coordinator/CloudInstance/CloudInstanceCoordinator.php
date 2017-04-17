<?php

namespace AppBundle\Coordinator\CloudInstance;

use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use Symfony\Component\Console\Output\OutputInterface;

interface CloudInstanceCoordinator
{
    public function __construct(array $credentials, Region $region, OutputInterface $output);

    public function triggerLaunchOfCloudInstance(CloudInstance $cloudInstance) : void;

    public function updateCloudInstanceWithCoordinatorSpecificInfoAfterLaunchWasTriggered(CloudInstance $cloudInstance) : void;

    public function cloudInstanceHasFinishedLaunchingOrStarting(CloudInstance $cloudInstance) : bool;

    public function getPublicAddressOfRunningCloudInstance(CloudInstance $cloudInstance) : string;

    public function getAdminPasswordForCloudInstance(CloudInstance $cloudInstance) : string;

    public function cloudInstanceWasAskedToStop(CloudInstance $cloudInstance) : bool;

    public function cloudInstanceHasFinishedStopping(CloudInstance $cloudInstance) : bool;

    public function cloudInstanceWasAskedToStart(CloudInstance $cloudInstance) : bool;

    public function cloudInstanceWasAskedToTerminate(CloudInstance $cloudInstance) : bool;

    public function cloudInstanceHasFinishedTerminating(CloudInstance $cloudInstance) : bool;
}
