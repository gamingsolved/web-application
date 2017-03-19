<?php

namespace AppBundle\Coordinator\CloudInstance;

use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use Symfony\Component\Console\Output\OutputInterface;

interface CloudInstanceCoordinator
{
    public function __construct(array $credentials, Region $region, OutputInterface $output);

    public function launch(CloudInstance $cloudInstance) : bool;

    public function hasFinishedLaunching(CloudInstance $cloudInstance) : bool;

    public function tryRetrievingAdminPassword(CloudInstance $cloudInstance, string $encryptionKey) : bool;

    //public static function getPublicAddress(CloudInstance $cloudInstance) : string;
}
