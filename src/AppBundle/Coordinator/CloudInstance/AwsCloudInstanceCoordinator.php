<?php

namespace AppBundle\Coordinator\CloudInstance;

use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use AppBundle\Utility\Cryptor;
use Aws\Sdk;
use Symfony\Component\Console\Output\OutputInterface;

class AwsCloudInstanceCoordinator implements CloudInstanceCoordinator
{
    const KEYPAIR_NAME = 'gaming-vm-keypair';
    const SECURITY_GROUP_NAME = 'gaming-vm-all-open';

    protected $keypairPrivateKey;
    protected $ec2Client;
    protected $output;

    public function __construct(array $credentials, Region $region, OutputInterface $output)
    {
        $this->keypairPrivateKey = $credentials['keypairPrivateKey'];
        $sdk = new Sdk(
            [
                'credentials' => [
                    'key' => $credentials['apiKey'],
                    'secret' => $credentials['apiSecret']
                ],
                'region' => $region->getInternalName(),
                'version' => '2016-11-15'
            ]
        );
        $this->ec2Client = $sdk->createEc2();
        $this->output = $output;
    }

    /**
     * param type differs intentionally
     *
     * @param AwsCloudInstance $cloudInstance
     * @return bool
     */
    public function launch(CloudInstance $cloudInstance) : bool
    {
        try {
            $result = $this->ec2Client->runInstances([
                'ImageId' => $cloudInstance->getImage()->getInternalName(),
                'MinCount' => 1,
                'MaxCount' => 1,
                'InstanceType' => $cloudInstance->getFlavor()->getInternalName(),
                'KeyName' => self::KEYPAIR_NAME,
                'SecurityGroups' => [self::SECURITY_GROUP_NAME]
            ]);

            $instanceId = $result['Instances'][0]['InstanceId'];

            $cloudInstance->setEc2InstanceId($instanceId);
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * param type differs intentionally
     *
     * @param AwsCloudInstance $cloudInstance
     * @return bool
     */
    public function hasFinishedLaunching(CloudInstance $cloudInstance) : bool
    {
        try {
            $result = $this->ec2Client->describeInstances([
                'InstanceIds' => [$cloudInstance->getEc2InstanceId()]
            ]);

            if ($result['Instances'][0]['State']['Name'] === 'running') {
                $cloudInstance->setPublicAddress(
                    $result['Instances'][0]['NetworkInterfaces']['Association']['PublicIp']
                );
            }
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * param type differs intentionally
     *
     * @param AwsCloudInstance $cloudInstance
     * @return bool
     */
    public function tryRetrievingAdminPassword(CloudInstance $cloudInstance, string $encryptionKey) : string
    {
        try {
            $result = $this->ec2Client->getPasswordData([
                'InstanceId' => $cloudInstance->getEc2InstanceId()
            ]);

            if ($result['PasswordData'] !== '') {
                $cryptor = new Cryptor();
                return $cryptor->encryptString(
                    'secret password',
                    $encryptionKey
                );
            }
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());
            return false;
        }

        return true;
    }
}
