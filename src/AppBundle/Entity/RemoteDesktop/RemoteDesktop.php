<?php

namespace AppBundle\Entity\RemoteDesktop;

use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\CloudInstance\PaperspaceCloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstanceInterface;
use AppBundle\Entity\CloudInstanceProvider\AwsCloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\PaperspaceCloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Flavor;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Image;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopRelevantForBillingEvent;
use AppBundle\Entity\User;
use AppBundle\Utility\DateTimeUtility;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Doctrine\DBAL\Types\Type;

Type::addType('RemoteDesktopKindType', 'AppBundle\Entity\RemoteDesktop\RemoteDesktopKindType');
Type::addType('CloudInstanceProviderType', 'AppBundle\Entity\CloudInstanceProvider\CloudInstanceProviderType');

/**
 * @ORM\Entity
 * @ORM\Table(name="remote_desktops")
 */
class RemoteDesktop
{
    const STREAMING_CLIENT_CGX = 0;

    const STATUS_NEVER_LAUNCHED = 0;
    const STATUS_BOOTING = 1;
    const STATUS_READY_TO_USE = 2;
    const STATUS_STOPPING = 3;
    const STATUS_STOPPED = 4;
    const STATUS_TERMINATING = 5;
    const STATUS_TERMINATED = 6;
    const STATUS_REBOOTING = 7;

    const COSTS_INTERVAL_HOURLY = 0;
    const COSTS_INTERVAL_MONTHLY = 1;

    const HASH_SECRET = '>"13!V{_:E7 KQ3*ttV,\n|^2,a""k~Q';

    /**
     * @var string
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var \AppBundle\Entity\User
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\User", inversedBy="remoteDesktops")
     * @ORM\JoinColumn(name="users_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @var string
     * @ORM\Column(name="title", type="string", length=128, nullable=false)
     */
    private $title = "";

    /**
     * @var RemoteDesktopKind
     * @ORM\Column(name="kind", type="RemoteDesktopKindType", nullable=false)
     */
    private $kind;

    /**
     * @var int
     * @ORM\Column(name="streaming_client", type="smallint", nullable=false)
     */
    private $streamingClient = self::STREAMING_CLIENT_CGX;

    /**
     * @var CloudInstanceProvider
     * @ORM\Column(name="cloud_instance_provider", type="CloudInstanceProviderType", nullable=false)
     */
    private $cloudInstanceProvider;

    /**
     * @var Collection|\AppBundle\Entity\CloudInstance\AwsCloudInstance
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\CloudInstance\AwsCloudInstance", mappedBy="remoteDesktop", cascade="all")
     */
    private $awsCloudInstances;

    /**
     * @var Collection|\AppBundle\Entity\CloudInstance\PaperspaceCloudInstance
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\CloudInstance\PaperspaceCloudInstance", mappedBy="remoteDesktop", cascade="all")
     */
    private $paperspaceCloudInstances;

    /**
     * @var Collection|\AppBundle\Entity\Billing\BillableItem
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\Billing\BillableItem", mappedBy="remoteDesktop", cascade="all")
     */
    private $billableItems;

    /**
     * @var Collection|\AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopRelevantForBillingEvent
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopRelevantForBillingEvent", mappedBy="remoteDesktop", cascade="all")
     */
    private $remoteDesktopRelevantForBillingEvents;

    /**
     * @var array
     */
    private $optimalHourlyAutostopTimes;

    public function __construct() {
        $this->awsCloudInstances = new ArrayCollection();
        $this->paperspaceCloudInstances = new ArrayCollection();
        $this->billableItems = new ArrayCollection();
        $this->remoteDesktopRelevantForBillingEvents = new ArrayCollection();
    }

    public function setId(string $id) : void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \AppBundle\Entity\User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return \AppBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    /**
     * @param RemoteDesktopKind $kind
     */
    public function setKind(RemoteDesktopKind $kind)
    {
        $this->kind = $kind;
    }

    /**
     * @return RemoteDesktopKind
     */
    public function getKind()
    {
        return $this->kind;
    }

    public function setCloudInstanceProvider(CloudInstanceProvider $cloudInstanceProvider)
    {
        $this->cloudInstanceProvider = $cloudInstanceProvider;
    }

    public function getCloudInstanceProvider() : CloudInstanceProvider
    {
        return $this->cloudInstanceProvider;
    }

    /**
     * @param CloudInstance $cloudInstance
     * @return void
     * @throws \Exception
     */
    public function addCloudInstance(CloudInstance $cloudInstance)
    {
        if ($this->cloudInstanceProvider instanceof AwsCloudInstanceProvider && $cloudInstance instanceof AwsCloudInstance) {
            $cloudInstance->setRemoteDesktop($this);
            $this->awsCloudInstances[] = $cloudInstance;
        } elseif ($this->cloudInstanceProvider instanceof PaperspaceCloudInstanceProvider && $cloudInstance instanceof PaperspaceCloudInstance) {
            $cloudInstance->setRemoteDesktop($this);
            $this->paperspaceCloudInstances[] = $cloudInstance;
        } else {
            throw new \Exception(
                'Cannot add cloud instance of class ' . get_class($cloudInstance) .
                ' because this remote desktop is configured for cloud instance provider ' . get_class($this->cloudInstanceProvider)
            );
        }
    }

    /**
     * @param RemoteDesktopRelevantForBillingEvent $remoteDesktopRelevantForBillingEvent
     * @return void
     * @throws \Exception
     */
    public function addRemoteDesktopRelevantForBillingEvent(RemoteDesktopRelevantForBillingEvent $remoteDesktopRelevantForBillingEvent)
    {
        $this->remoteDesktopRelevantForBillingEvents->add($remoteDesktopRelevantForBillingEvent);
    }

    /**
     * @return Collection[RemoteDesktopRelevantForBillingEvent]
     */
    public function getRemoteDesktopRelevantForBillingEvents(): Collection
    {
        return $this->remoteDesktopRelevantForBillingEvents;
    }

    /**
     * @return Collection[CloudInstance]
     */
    public function getCloudInstances() : Collection
    {
        if ($this->cloudInstanceProvider instanceof AwsCloudInstanceProvider) {
            return $this->awsCloudInstances;
        } elseif ($this->cloudInstanceProvider instanceof PaperspaceCloudInstanceProvider) {
            return $this->paperspaceCloudInstances;
        } else {
            throw new \Exception(
                'Cannot get cloud instances of class ' . get_class($this->cloudInstanceProvider)
            );
        }
    }

    public function getStatus() : int
    {
        $status = null;

        $cloudInstances = $this->getCloudInstances();

        if ($cloudInstances->isEmpty()) {
            $status = self::STATUS_NEVER_LAUNCHED;
        } else {
            $activeCloudInstance = $this->getActiveCloudInstance();

            switch ($activeCloudInstance->getRunstatus()) {
                case CloudInstance::RUNSTATUS_SCHEDULED_FOR_LAUNCH:
                case CloudInstance::RUNSTATUS_LAUNCHING:
                case CloudInstance::RUNSTATUS_SCHEDULED_FOR_START:
                case CloudInstance::RUNSTATUS_STARTING:
                $status = self::STATUS_BOOTING;
                    break;
                case CloudInstance::RUNSTATUS_RUNNING:
                    $status = self::STATUS_READY_TO_USE;
                    break;
                case CloudInstance::RUNSTATUS_SCHEDULED_FOR_STOP:
                case CloudInstance::RUNSTATUS_STOPPING:
                    $status = self::STATUS_STOPPING;
                    break;
                case CloudInstance::RUNSTATUS_STOPPED:
                    $status = self::STATUS_STOPPED;
                    break;
                case CloudInstance::RUNSTATUS_SCHEDULED_FOR_TERMINATION:
                case CloudInstance::RUNSTATUS_TERMINATING:
                    $status = self::STATUS_TERMINATING;
                    break;
                case CloudInstance::RUNSTATUS_TERMINATED:
                    $status = self::STATUS_TERMINATED;
                    break;
                case CloudInstance::RUNSTATUS_SCHEDULED_FOR_REBOOT:
                case CloudInstance::RUNSTATUS_REBOOTING:
                    $status = self::STATUS_REBOOTING;
                    break;
                default:
                    throw new \Exception('Unexpected cloud instance runstatus ' . $activeCloudInstance->getRunstatus());

            }
        }

        return $status;
    }

    public function statusToStatusLabel(int $status): string
    {
        switch ($status) {
            case self::STATUS_NEVER_LAUNCHED:
                return 'never_launched';
                break;
            case self::STATUS_BOOTING:
                return 'booting';
                break;
            case self::STATUS_READY_TO_USE:
                return 'ready_to_use';
                break;
            case self::STATUS_STOPPING:
                return 'stopping';
                break;
            case self::STATUS_STOPPED:
                return 'stopped';
                break;
            case self::STATUS_TERMINATING:
                return 'terminating';
                break;
            case self::STATUS_TERMINATED:
                return 'terminated';
                break;
            case self::STATUS_REBOOTING:
                return 'rebooting';
                break;
            default:
                throw new \Exception('Unknown remoteDesktop status value ' .$status);
        }
    }

    public function getStatusLabel() : string
    {
        return $this->statusToStatusLabel($this->getStatus());
    }

    public function getPublicAddress() : string
    {
        return $this->getActiveCloudInstance()->getPublicAddress();
    }

    public function getAdminPassword() : string
    {
        return $this->getActiveCloudInstance()->getAdminPassword();
    }

    public function getFlavorOfActiveCloudInstance() : Flavor
    {
        return $this->getActiveCloudInstance()->getFlavor();
    }

    public function getImageOfActiveCloudInstance() : Image
    {
        return $this->getActiveCloudInstance()->getImage();
    }

    public function getRegionOfActiveCloudInstance() : Region
    {
        return $this->getActiveCloudInstance()->getRegion();
    }

    public function scheduleForStop()
    {
        $activeCloudInstance = $this->getActiveCloudInstance();

        if ($activeCloudInstance->getRunstatus() == CloudInstance::RUNSTATUS_RUNNING) {
            $this->getActiveCloudInstance()->setRunstatus(CloudInstance::RUNSTATUS_SCHEDULED_FOR_STOP);
        } else {
            throw new \Exception('Cannot schedule a cloud instance for stopping that is not running');
        }
    }

    public function setScheduleForStopAt(\DateTime $dateTime)
    {
        $this->getActiveCloudInstance()->setScheduleForStopAt($dateTime);
    }

    public function scheduleForStart()
    {
        $activeCloudInstance = $this->getActiveCloudInstance();

        if ($activeCloudInstance->getRunstatus() == CloudInstance::RUNSTATUS_STOPPED) {
            $this->getActiveCloudInstance()->setRunstatus(CloudInstance::RUNSTATUS_SCHEDULED_FOR_START);
        } else {
            throw new \Exception('Cannot schedule a cloud instance for starting that is not stopped');
        }
    }

    public function scheduleForTermination()
    {
        $activeCloudInstance = $this->getActiveCloudInstance();

        if (   $activeCloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_STOPPED
            || $activeCloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_RUNNING )
        {
            $this->getActiveCloudInstance()->setRunstatus(CloudInstance::RUNSTATUS_SCHEDULED_FOR_TERMINATION);
        } else {
            throw new \Exception('Cannot schedule a cloud instance for termination that is not either running or stopped');
        }
    }

    public function scheduleForReboot()
    {
        $activeCloudInstance = $this->getActiveCloudInstance();

        if ($activeCloudInstance->getRunstatus() === CloudInstance::RUNSTATUS_RUNNING) {
            $this->getActiveCloudInstance()->setRunstatus(CloudInstance::RUNSTATUS_SCHEDULED_FOR_REBOOT);
        } else {
            throw new \Exception('Cannot schedule a cloud instance for reboot that is not running');
        }
    }

    public function getUsageCostsInterval() : int
    {
        return $this->getActiveCloudInstance()->getUsageCostsInterval();
    }

    public function getProvisioningCostsInterval() : int
    {
        return $this->getActiveCloudInstance()->getProvisioningCostsInterval();
    }

    protected function getCostsIntervalAsString(int $costsIntervalIntValue) : string
    {
        switch ($costsIntervalIntValue) {
            case RemoteDesktop::COSTS_INTERVAL_HOURLY:
                return 'hourly';
                break;
            case RemoteDesktop::COSTS_INTERVAL_MONTHLY:
                return 'monthly';
                break;
            default:
                throw new \Exception('Unknown costs interval ' . $this->getUsageCostsInterval());
        }
    }

    public function getUsageCostsIntervalAsString() : string
    {
        return $this->getCostsIntervalAsString($this->getUsageCostsInterval());
    }

    public function getProvisioningCostsIntervalAsString() : string
    {
        return $this->getCostsIntervalAsString($this->getProvisioningCostsInterval());
    }

    public function getUsageCostsForOneInterval() : float
    {
        return $this->getActiveCloudInstance()->getUsageCostsForOneInterval();
    }

    public function getProvisioningCostsForOneInterval() : float
    {
        return $this->getActiveCloudInstance()->getProvisioningCostsForOneInterval();
    }

    public function getScheduledForStopAt()
    {
        return $this->getActiveCloudInstance()->getScheduleForStopAt();
    }

    /**
     * @throws \Exception
     */
    public function getActiveCloudInstance() : CloudInstanceInterface {
        $cloudInstances = $this->getCloudInstances();
        /** @var CloudInstance $cloudInstance */
        foreach ($cloudInstances as $cloudInstance) {
            if ($cloudInstance->getStatus() == CloudInstance::STATUS_IN_USE) {
                return $cloudInstance;
            }
        }
        throw new \Exception('Could not find the active instance for remote desktop ' . $this->getId());
    }

    public function getIdHash() : string
    {
        return sha1(self::HASH_SECRET . $this->getId());
    }

    public function setOptimalHourlyAutostopTimes(array $optimalHourlyAutostopTimes) : void
    {
        $this->optimalHourlyAutostopTimes = $optimalHourlyAutostopTimes;
    }

    public function getOptimalHourlyAutostopTimes() : array
    {
        return $this->optimalHourlyAutostopTimes;
    }

}
