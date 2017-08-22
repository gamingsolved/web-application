<?php

namespace AppBundle\Coordinator\CloudInstance;

use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use Symfony\Component\Console\Output\OutputInterface;

interface CloudInstanceCoordinatorInterface
{
    public function __construct(array $credentials, Region $region, OutputInterface $output);

    public function triggerLaunchOfCloudInstance(CloudInstance $cloudInstance) : void;

    public function updateCloudInstanceWithProviderSpecificInfoAfterLaunchWasTriggered(CloudInstance $cloudInstance) : void;

    public function triggerStartOfCloudInstance(CloudInstance $cloudInstance) : void;

    public function cloudInstanceIsRunning(CloudInstance $cloudInstance) : bool;

    public function getPublicAddressOfRunningCloudInstance(CloudInstance $cloudInstance);

    public function getAdminPasswordOfRunningCloudInstance(CloudInstance $cloudInstance);

    public function triggerStopOfCloudInstance(CloudInstance $cloudInstance) : void;

    public function cloudInstanceIsStopped(CloudInstance $cloudInstance) : bool;

    public function triggerTerminationOfCloudInstance(CloudInstance $cloudInstance) : void;

    public function cloudInstanceIsTerminated(CloudInstance $cloudInstance) : bool;

    public function triggerRebootOfCloudInstance(CloudInstance $cloudInstance) : void;
}

class CloudProviderProblemException extends \RuntimeException
{
    const CODE_OUT_OF_INSTANCE_CAPACITY = 0;
    const CODE_INSTANCE_UNKNOWN = 1;
    const CODE_GENERAL_PROBLEM = 2;
}
