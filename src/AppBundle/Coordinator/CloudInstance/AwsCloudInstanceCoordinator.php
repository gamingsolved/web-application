<?php

namespace AppBundle\Coordinator\CloudInstance;

use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
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
    public function cloudInstanceWasLaunched(CloudInstance $cloudInstance) : bool
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
    public function cloudInstanceHasFinishedLaunchingOrStarting(CloudInstance $cloudInstance) : bool
    {
        try {
            $result = $this->ec2Client->describeInstances([
                'InstanceIds' => [$cloudInstance->getEc2InstanceId()]
            ]);

            if ($result['Reservations'][0]['Instances'][0]['State']['Name'] === 'running') {
                $cloudInstance->setPublicAddress(
                    $result['Reservations'][0]['Instances'][0]['NetworkInterfaces'][0]['Association']['PublicIp']
                );
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());
            return false;
        }
    }

    /**
     * param type differs intentionally
     *
     * @param AwsCloudInstance $cloudInstance
     * @return bool
     */
    public function cloudInstanceAdminPasswordCouldBeRetrieved(CloudInstance $cloudInstance, string $encryptionKey) : bool
    {
        try {
            $result = $this->ec2Client->getPasswordData([
                'InstanceId' => $cloudInstance->getEc2InstanceId()
            ]);

            if ($result['PasswordData'] !== '') {
                $base64Pwd = $result['PasswordData'];
                $encryptedPwd = base64_decode($base64Pwd);
                $cleartextPwd = '';

                $keypairPrivateKeyResource = openssl_get_privatekey($this->keypairPrivateKey);

                if (openssl_private_decrypt($encryptedPwd, $cleartextPwd, $keypairPrivateKeyResource)) {
                    $cloudInstance->setAdminPassword($cleartextPwd);
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());
            return false;
        }
    }

    /**
     * @param AwsCloudInstance $cloudInstance param type differs intentionally
     * @return bool
     */
    public function cloudInstanceWasAskedToStop(CloudInstance $cloudInstance) : bool
    {
        try {
            $this->ec2Client->stopInstances([
                'InstanceIds' => [$cloudInstance->getEc2InstanceId()]
            ]);
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * @param AwsCloudInstance $cloudInstance param type differs intentionally
     * @return bool
     */
    public function cloudInstanceHasFinishedStopping(CloudInstance $cloudInstance) : bool
    {
        try {
            $result = $this->ec2Client->describeInstances([
                'InstanceIds' => [$cloudInstance->getEc2InstanceId()]
            ]);

            if ($result['Reservations'][0]['Instances'][0]['State']['Name'] === 'stopped') {
                $cloudInstance->setPublicAddress('');
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());
            return false;
        }
    }

    /**
     * @param AwsCloudInstance $cloudInstance param type differs intentionally
     * @return bool
     */
    public function cloudInstanceWasAskedToStart(CloudInstance $cloudInstance) : bool
    {
        try {
            $this->ec2Client->startInstances([
                'InstanceIds' => [$cloudInstance->getEc2InstanceId()]
            ]);
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * @param AwsCloudInstance $cloudInstance param type differs intentionally
     * @return bool
     */
    public function cloudInstanceWasAskedToTerminate(CloudInstance $cloudInstance) : bool
    {
        try {
            $this->ec2Client->terminateInstances([
                'InstanceIds' => [$cloudInstance->getEc2InstanceId()]
            ]);
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * @param AwsCloudInstance $cloudInstance param type differs intentionally
     * @return bool
     */
    public function cloudInstanceHasFinishedTerminating(CloudInstance $cloudInstance) : bool
    {
        try {
            $result = $this->ec2Client->describeInstances([
                'InstanceIds' => [$cloudInstance->getEc2InstanceId()]
            ]);

            if ($result['Reservations'][0]['Instances'][0]['State']['Name'] === 'terminated') {
                $cloudInstance->setPublicAddress('');
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());
            return false;
        }
    }
}
