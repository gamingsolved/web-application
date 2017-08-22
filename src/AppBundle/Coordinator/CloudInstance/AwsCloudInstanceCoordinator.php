<?php

namespace AppBundle\Coordinator\CloudInstance;

use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use Aws\Ec2\Exception\Ec2Exception;
use Aws\Sdk;
use Symfony\Component\Console\Output\OutputInterface;

class AwsCloudInstanceCoordinator implements CloudInstanceCoordinatorInterface
{
    const KEYPAIR_NAME = 'ubiqmachine-default';
    const SECURITY_GROUP_NAME = 'ubiqmachine-cgxclient-default';

    protected $keypairPrivateKey;
    protected $ec2Client;
    protected $output;

    protected $cloudInstanceIds2Ec2InstanceIds = [];

    /**
     * AwsCloudInstanceCoordinator constructor.
     * @param array $credentials
     * @param Region $region
     * @param OutputInterface $output
     * @param null|\Aws\Ec2\Ec2Client $paperspaceMachinesApiClient If provided, this constructor does not build its own ec2 API client
     */
    public function __construct(array $credentials, Region $region, OutputInterface $output, $paperspaceMachinesApiClient = null)
    {
        $this->keypairPrivateKey = $credentials['keypairPrivateKey'];
        if (!is_null($paperspaceMachinesApiClient)) {
            $this->ec2Client = $paperspaceMachinesApiClient;
        } else {
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
        }
        $this->output = $output;
    }

    /**
     * param type differs intentionally
     *
     * @param AwsCloudInstance $cloudInstance
     */
    public function triggerLaunchOfCloudInstance(CloudInstance $cloudInstance) : void
    {
        $parameters = [
            'ImageId' => $cloudInstance->getImage()->getInternalName(),
            'MinCount' => 1,
            'MaxCount' => 1,
            'InstanceType' => $cloudInstance->getFlavor()->getInternalName(),
            'KeyName' => self::KEYPAIR_NAME,
            'SecurityGroups' => [self::SECURITY_GROUP_NAME]
        ];

        if ($cloudInstance->getAdditionalVolumeSize() > 0) {
            $parameters['BlockDeviceMappings'][0] = [
                'DeviceName' => 'xvdh',
                'Ebs' => [
                    'DeleteOnTermination' => true,
                    'Encrypted' => false,
                    'VolumeType' => 'gp2',
                    'VolumeSize' => $cloudInstance->getAdditionalVolumeSize()
                ]
            ];
        }

        try {
            $result = $this->ec2Client->runInstances($parameters);
            $this->cloudInstanceIds2Ec2InstanceIds[$cloudInstance->getId()] = $result['Instances'][0]['InstanceId'];
        } catch (Ec2Exception $e) {
            if ($e->getAwsErrorCode() === 'InsufficientInstanceCapacity') {
                throw new CloudProviderProblemException('', CloudProviderProblemException::CODE_OUT_OF_INSTANCE_CAPACITY, $e);
            }
        }
    }

    /**
     * param type differs intentionally
     *
     * @param AwsCloudInstance $cloudInstance
     */
    public function updateCloudInstanceWithProviderSpecificInfoAfterLaunchWasTriggered(CloudInstance $cloudInstance) : void
    {
        $cloudInstance->setEc2InstanceId($this->cloudInstanceIds2Ec2InstanceIds[$cloudInstance->getId()]);
    }

    /**
     * @param AwsCloudInstance $cloudInstance param type differs intentionally
     */
    public function triggerStartOfCloudInstance(CloudInstance $cloudInstance) : void
    {
        $this->ec2Client->startInstances([
            'InstanceIds' => [$cloudInstance->getEc2InstanceId()]
        ]);
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
     * @return null|string
     */
    public function getPublicAddressOfRunningCloudInstance(CloudInstance $cloudInstance)
    {
        try {
            $result = $this->ec2Client->describeInstances([
                'InstanceIds' => [$cloudInstance->getEc2InstanceId()]
            ]);

            if ($result['Reservations'][0]['Instances'][0]['State']['Name'] === 'running') {
                if (array_key_exists(0, $result['Reservations'][0]['Instances'][0]['NetworkInterfaces'])) {
                    $ip = $result['Reservations'][0]['Instances'][0]['NetworkInterfaces'][0]['Association']['PublicIp'];
                } else {
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
     * @return null|string
     */
    public function getAdminPasswordOfRunningCloudInstance(CloudInstance $cloudInstance)
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
     */
    public function triggerTerminationOfCloudInstance(CloudInstance $cloudInstance) : void
    {
        try {
            $this->ec2Client->terminateInstances([
                'InstanceIds' => [$cloudInstance->getEc2InstanceId()]
            ]);
        } catch (Ec2Exception $e) {
            if ($e->getAwsErrorCode() === 'InvalidInstanceID.NotFound') {
                throw new CloudProviderProblemException('', CloudProviderProblemException::CODE_INSTANCE_UNKNOWN, $e);
            }
        }
    }

    /**
     * @param AwsCloudInstance $cloudInstance param type differs intentionally
     * @return bool
     */
    public function cloudInstanceIsTerminated(CloudInstance $cloudInstance) : bool
    {
        try {
            $result = $this->ec2Client->describeInstances([
                'InstanceIds' => [$cloudInstance->getEc2InstanceId()]
            ]);

            if ($result['Reservations'][0]['Instances'][0]['State']['Name'] === 'terminated') {
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
     */
    public function triggerRebootOfCloudInstance(CloudInstance $cloudInstance) : void
    {
        $this->ec2Client->rebootInstances([
            'InstanceIds' => [$cloudInstance->getEc2InstanceId()]
        ]);
    }
}
