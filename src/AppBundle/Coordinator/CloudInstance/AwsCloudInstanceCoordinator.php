<?php

namespace AppBundle\Coordinator\CloudInstance;

use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use Aws\Sdk;
use Symfony\Component\Console\Output\OutputInterface;

class AwsCloudInstanceCoordinator implements CloudInstanceCoordinator
{
    const KEYPAIR_NAME = 'ubiqmachine-default';
    const SECURITY_GROUP_NAME = 'ubiqmachine-cgxclient-default';

    protected $keypairPrivateKey;
    protected $ec2Client;
    protected $output;

    protected $cloudInstanceIds2Ec2InstanceIds = [];

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
     */
    public function triggerLaunchOfCloudInstance(CloudInstance $cloudInstance) : void
    {
        $result = $this->ec2Client->runInstances([
            'ImageId' => $cloudInstance->getImage()->getInternalName(),
            'MinCount' => 1,
            'MaxCount' => 1,
            'InstanceType' => $cloudInstance->getFlavor()->getInternalName(),
            'KeyName' => self::KEYPAIR_NAME,
            'SecurityGroups' => [self::SECURITY_GROUP_NAME]
        ]);

        $this->cloudInstanceIds2Ec2InstanceIds[$cloudInstance->getId()] = $result['Instances'][0]['InstanceId'];
    }

    /**
     * param type differs intentionally
     *
     * @param AwsCloudInstance $cloudInstance
     */
    public function updateCloudInstanceWithCoordinatorSpecificInfoAfterLaunchWasTriggered(CloudInstance $cloudInstance) : void
    {
        $cloudInstance->setEc2InstanceId($this->cloudInstanceIds2Ec2InstanceIds[$cloudInstance->getId()]);
    }

    /**
     * param type differs intentionally
     *
     * @param AwsCloudInstance $cloudInstance
     * @return bool
     */
    public function cloudInstanceIsRunning(CloudInstance $cloudInstance) : bool
    {
        try {
            $result = $this->ec2Client->describeInstances([
                'InstanceIds' => [$cloudInstance->getEc2InstanceId()]
            ]);

            if ($result['Reservations'][0]['Instances'][0]['State']['Name'] === 'running') {
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
     */
    public function getPublicAddressOfRunningCloudInstance(CloudInstance $cloudInstance) : string
    {
        try {
            $result = $this->ec2Client->describeInstances([
                'InstanceIds' => [$cloudInstance->getEc2InstanceId()]
            ]);

            if ($result['Reservations'][0]['Instances'][0]['State']['Name'] === 'running') {
                $ip = $result['Reservations'][0]['Instances'][0]['NetworkInterfaces'][0]['Association']['PublicIp'];

                // IP address is in other field...
                if (is_null($ip)) {
                    $ip = $result['Reservations'][0]['Instances'][0]['PublicIpAddress'];
                }
                return $ip;
            } else {
                return null;
            }
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());
            return null;
        }
    }

    /**
     * param type differs intentionally
     *
     * @param AwsCloudInstance $cloudInstance
     */
    public function getAdminPasswordOfRunningCloudInstance(CloudInstance $cloudInstance) : string
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
                    return $cleartextPwd;
                } else {
                    throw new \Exception('Could not decrypt admin password');
                }
            } else {
                return null;
            }
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());
            return null;
        }
    }

    /**
     * @param AwsCloudInstance $cloudInstance param type differs intentionally
     */
    public function triggerStopOfCloudInstance(CloudInstance $cloudInstance) : void
    {
        $this->ec2Client->stopInstances([
            'InstanceIds' => [$cloudInstance->getEc2InstanceId()]
        ]);
    }

    /**
     * @param AwsCloudInstance $cloudInstance param type differs intentionally
     * @return bool
     */
    public function cloudInstanceIsStopped(CloudInstance $cloudInstance) : bool
    {
        try {
            $result = $this->ec2Client->describeInstances([
                'InstanceIds' => [$cloudInstance->getEc2InstanceId()]
            ]);

            if ($result['Reservations'][0]['Instances'][0]['State']['Name'] === 'stopped') {
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
