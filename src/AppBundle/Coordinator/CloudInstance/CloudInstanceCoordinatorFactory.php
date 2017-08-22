<?php

namespace AppBundle\Coordinator\CloudInstance;

use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstance\PaperspaceCloudInstance;
use Symfony\Component\Console\Output\OutputInterface;

class CloudInstanceCoordinatorFactory
{
    public function getCloudInstanceCoordinatorForCloudInstance(
        CloudInstance $cloudInstance,
        string $awsApiKey,
        string $awsApiSecret,
        string $awsKeypairPrivateKeyFile,
        string $paperspaceApiKey,
        OutputInterface $output) : CloudInstanceCoordinatorInterface
    {
        if ($cloudInstance instanceof AwsCloudInstance) {
            return new AwsCloudInstanceCoordinator(
                [
                    'apiKey' => $awsApiKey,
                    'apiSecret' => $awsApiSecret,
                    'keypairPrivateKey' => file_get_contents($awsKeypairPrivateKeyFile)
                ],
                $cloudInstance->getRegion(),
                $output
            );
        } elseif ($cloudInstance instanceof PaperspaceCloudInstance) {
            return new PaperspaceCloudInstanceCoordinator(
                [
                    'apiKey' => $paperspaceApiKey
                ],
                $cloudInstance->getRegion(),
                $output
            );
        } else {
            throw new \Exception('No cloud instance coordinator for cloud instances of class ' . get_class($cloudInstance));
        }
    }
}
