<?php

namespace AppBundle\Coordinator\CloudInstance;

use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use Symfony\Component\Console\Output\OutputInterface;

interface CloudInstanceCoordinator
{
    public function __construct(array $credentials, Region $region, OutputInterface $output);

    public function cloudInstanceWasLaunched(CloudInstance $cloudInstance) : bool;

    public function cloudInstanceHasFinishedLaunching(CloudInstance $cloudInstance) : bool;

    public function cloudInstanceAdminPasswordCouldBeRetrieved(CloudInstance $cloudInstance, string $encryptionKey) : bool;

    //public static function getPublicAddress(CloudInstance $cloudInstance) : string;
}
