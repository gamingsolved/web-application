<?php

namespace AppBundle\Coordinator\CloudInstance;

use AppBundle\Entity\CloudInstance\PaperspaceCloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use Gamingsolved\Paperspace\Api\Client\Version0_1_3 as PaperspaceApiClient;
use Gamingsolved\Paperspace\Api\Client\Version0_1_3\Api\MachinesApi as PaperspaceMachinesApiClient;
use Gamingsolved\Paperspace\Api\Client\Version0_1_3\Model\Machine as PaperspaceMachine;
use Symfony\Component\Console\Output\OutputInterface;

class PaperspaceCloudInstanceCoordinator implements CloudInstanceCoordinatorInterface
{
    protected $output;
    protected $paperspaceMachinesApiClient;

    protected $cloudInstanceIds2PsInstanceIds = [];

    /**
     * @param array $credentials
     * @param Region $region
     * @param OutputInterface $output
     * @param null|PaperspaceMachinesApiClient $paperspaceMachinesApiClient If provided, this constructor does not build its own API client
     */
    public function __construct(array $credentials, Region $region, OutputInterface $output, $paperspaceMachinesApiClient = null)
    {
        if (!is_null($paperspaceMachinesApiClient)) {
            $this->paperspaceMachinesApiClient = $paperspaceMachinesApiClient;
        } else {
            $config = PaperspaceApiClient\Configuration::getDefaultConfiguration();
            $config->setApiKey('X-API-Key', $credentials['apiKey']);
            $this->paperspaceMachinesApiClient = new PaperspaceApiClient\Api\MachinesApi(null, $config);
        }
        $this->output = $output;
    }

    /**
     * param type differs intentionally
     *
     * @param PaperspaceCloudInstance $cloudInstance
     */
    public function triggerLaunchOfCloudInstance(CloudInstance $cloudInstance) : void
    {
        $createMachineParams = new PaperspaceApiClient\Model\CreateMachineParams();
        $createMachineParams->setRegion($cloudInstance->getRegion()->getInternalName());
        $createMachineParams->setMachineType($cloudInstance->getFlavor()->getInternalName());
        $createMachineParams->setSize($cloudInstance->getRootVolumeSize());
        $createMachineParams->setBillingType('hourly');
        $createMachineParams->setMachineName(
            'GamingSolved machine for remoteDesktop id '
            . $cloudInstance->getRemoteDesktop()->getId()
            . ' of user ' . $cloudInstance->getRemoteDesktop()->getUser()->getUsername()
            . ' (' . $cloudInstance->getRemoteDesktop()->getUser()->getId() . ')'
        );
        $createMachineParams->setTemplateId($cloudInstance->getImage()->getInternalName());
        $createMachineParams->setAssignPublicIp(true);

        try {
            $machine = $this->paperspaceMachinesApiClient->createMachine($createMachineParams);
            $this->cloudInstanceIds2PsInstanceIds[$cloudInstance->getId()] = $machine->getId();
        } catch (\Exception $e) {
            throw new CloudProviderProblemException('Unknown Paperspace error', CloudProviderProblemException::CODE_GENERAL_PROBLEM, $e);
        }
    }

    /**
     * param type differs intentionally
     *
     * @param PaperspaceCloudInstance $cloudInstance
     */
    public function updateCloudInstanceWithProviderSpecificInfoAfterLaunchWasTriggered(CloudInstance $cloudInstance) : void
    {
        if (array_key_exists($cloudInstance->getId(), $this->cloudInstanceIds2PsInstanceIds)) {
            $cloudInstance->setPsInstanceId($this->cloudInstanceIds2PsInstanceIds[$cloudInstance->getId()]);
        } else {
            throw new \Exception('Cloud instance id ' . $cloudInstance->getId() . ' is not known to coordinator.');
        }

    }

    /**
     * @param PaperspaceCloudInstance $cloudInstance param type differs intentionally
     */
    public function triggerStartOfCloudInstance(CloudInstance $cloudInstance) : void
    {
        try {
            $this->paperspaceMachinesApiClient->startMachine($cloudInstance->getPsInstanceId());
        } catch (\Exception $e) {
            if ($e->getCode() === 404) {
                throw new CloudProviderProblemException('', CloudProviderProblemException::CODE_INSTANCE_UNKNOWN, $e);
            } else {
                throw new CloudProviderProblemException('', CloudProviderProblemException::CODE_GENERAL_PROBLEM, $e);
            }
        }
    }

    /**
     * param type differs intentionally
     *
     * @param PaperspaceCloudInstance $cloudInstance
     * @return bool
     */
    public function cloudInstanceIsRunning(CloudInstance $cloudInstance) : bool
    {
        try {
            $machine = $this->paperspaceMachinesApiClient->showMachine($cloudInstance->getPsInstanceId());

            if ($machine->getState() === PaperspaceMachine::STATE_READY) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());
            if ($e->getCode() === 404) {
                throw new CloudProviderProblemException('instance not found', CloudProviderProblemException::CODE_INSTANCE_UNKNOWN);
            } else {
                return false;
            }
        }
    }

    /**
     * param type differs intentionally
     *
     * @param PaperspaceCloudInstance $cloudInstance
     * @return null|string
     */
    public function getPublicAddressOfRunningCloudInstance(CloudInstance $cloudInstance)
    {
        try {
            $machine = $this->paperspaceMachinesApiClient->showMachine($cloudInstance->getPsInstanceId());

            if ($machine->getPublicIpAddress() !== '') {
                return $machine->getPublicIpAddress();
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
     * @param PaperspaceCloudInstance $cloudInstance
     * @return null|string
     */
    public function getAdminPasswordOfRunningCloudInstance(CloudInstance $cloudInstance)
    {
        return 'GamingSolved123';
    }

    /**
     * @param PaperspaceCloudInstance $cloudInstance param type differs intentionally
     */
    public function triggerStopOfCloudInstance(CloudInstance $cloudInstance) : void
    {
        try {
            $this->paperspaceMachinesApiClient->stopMachine($cloudInstance->getPsInstanceId());
        } catch (\Exception $e) {
            if ($e->getCode() === 404) {
                throw new CloudProviderProblemException(
                    'Instance with id ' . $cloudInstance->getPsInstanceId() . ' is not known at Paperspace.',
                    CloudProviderProblemException::CODE_INSTANCE_UNKNOWN, $e
                );
            } else {
                throw new CloudProviderProblemException('General Paperspace API problem.', CloudProviderProblemException::CODE_GENERAL_PROBLEM, $e);
            }
            /*
             [401] Client error: `POST https://api.paperspace.io/machines/ps7c8hwb/stop` resulted in a `401 Unauthorized` response:
            {"status":401,"message":"API token request quota reached"}
             */
        }
    }

    /**
     * @param PaperspaceCloudInstance $cloudInstance param type differs intentionally
     * @return bool
     */
    public function cloudInstanceIsStopped(CloudInstance $cloudInstance) : bool
    {
        try {
            $machine = $this->paperspaceMachinesApiClient->showMachine($cloudInstance->getPsInstanceId());

            if ($machine->getState() === PaperspaceMachine::STATE_OFF) {
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
     * @param PaperspaceCloudInstance $cloudInstance param type differs intentionally
     */
    public function triggerTerminationOfCloudInstance(CloudInstance $cloudInstance) : void
    {
        try {
            $this->paperspaceMachinesApiClient->destroyMachine($cloudInstance->getPsInstanceId());
        } catch (\Exception $e) {
            if ($e->getCode() === 404) {
                throw new CloudProviderProblemException('', CloudProviderProblemException::CODE_INSTANCE_UNKNOWN, $e);
            } else {
                throw new CloudProviderProblemException('', CloudProviderProblemException::CODE_GENERAL_PROBLEM, $e);
            }
        }
    }

    /**
     * @param PaperspaceCloudInstance $cloudInstance param type differs intentionally
     * @return bool
     */
    public function cloudInstanceIsTerminated(CloudInstance $cloudInstance) : bool
    {
        try {
            $this->paperspaceMachinesApiClient->showMachine($cloudInstance->getPsInstanceId());
            return false;
        } catch (\Exception $e) {
            if ($e->getCode() === 404) {
                return true;
            } else {
                throw new CloudProviderProblemException('', CloudProviderProblemException::CODE_GENERAL_PROBLEM, $e);
            }
        }
    }

    /**
     * @param PaperspaceCloudInstance $cloudInstance param type differs intentionally
     */
    public function triggerRebootOfCloudInstance(CloudInstance $cloudInstance) : void
    {
        try {
            $this->paperspaceMachinesApiClient->restartMachine($cloudInstance->getPsInstanceId());
        } catch (\Exception $e) {
            if ($e->getCode() === 404) {
                throw new CloudProviderProblemException('', CloudProviderProblemException::CODE_INSTANCE_UNKNOWN, $e);
            } else {
                throw new CloudProviderProblemException('', CloudProviderProblemException::CODE_GENERAL_PROBLEM, $e);
            }
        }
    }
}
