<?php

namespace AppBundle\Entity\CloudInstance;

use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProviderInterface;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Flavor;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Image;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopRelevantForBillingEvent;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Utility\Cryptor;
use AppBundle\Utility\DateTimeUtility;
use Doctrine\ORM\Mapping as ORM;

interface CloudInstanceInterface
{
    public function getId() : string;

    public function getCloudInstanceProvider() : CloudInstanceProviderInterface;

    public function getHourlyUsageCosts() : float;
    public function getHourlyProvisioningCosts() : float;

    public function setStatus(int $status);
    public function getStatus() : int;

    public function setRunstatus(int $runstatus);
    public function getRunstatus() : int;

    public function setFlavor(Flavor $flavor);
    public function getFlavor() : Flavor;

    public function setImage(Image $image);
    public function getImage() : Image;

    public function setRegion(Region $region);
    public function getRegion() : Region;

    public function setRootVolumeSize(int $sizeInGB);
    public function getRootVolumeSize() : int;

    public function setAdditionalVolumeSize(int $sizeInGB);
    public function getAdditionalVolumeSize() : int;

    public function setRemoteDesktop(RemoteDesktop $remoteDesktop);
    public function getRemoteDesktop() : RemoteDesktop;

    public function setAdminPassword(string $password);
    public function getAdminPassword() : string;

    public function setPublicAddress(string $addr);
    public function getPublicAddress() : string;

    public function setScheduleForStopAt(\DateTime $dateTime);
    public function getScheduleForStopAt();

    public function getProviderInstanceId() : string;
}

abstract class CloudInstance implements CloudInstanceInterface
{
    const STATUS_IN_USE = 0;
    const STATUS_ARCHIVED = 1;

    const RUNSTATUS_NEVER_SET = -1; // This value is not meant to be persisted
    const RUNSTATUS_SCHEDULED_FOR_LAUNCH = 0;
    const RUNSTATUS_LAUNCHING = 1;
    const RUNSTATUS_RUNNING = 2;
    const RUNSTATUS_SCHEDULED_FOR_STOP = 3;
    const RUNSTATUS_STOPPING = 4;
    const RUNSTATUS_STOPPED = 5;
    const RUNSTATUS_SCHEDULED_FOR_START = 6;
    const RUNSTATUS_STARTING = 7;
    const RUNSTATUS_SCHEDULED_FOR_TERMINATION = 8;
    const RUNSTATUS_TERMINATING = 9;
    const RUNSTATUS_TERMINATED = 10;
    const RUNSTATUS_SCHEDULED_FOR_REBOOT = 11;
    const RUNSTATUS_REBOOTING = 12;
    const RUNSTATUS_ERROR_GENERAL = 100;

    const ERROR_INSUFFICIENT_PROVIDER_CAPACITY = 0;
    const ERROR_VPC_NEEDED = 1;

    const ADMIN_PASSWORD_ENCRYPTION_KEY = '06c528c143c3f5c73ae200048782bd422a4f1b90';

    /**
     * @var string
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var RemoteDesktop
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\RemoteDesktop\RemoteDesktop", inversedBy="awsCloudInstances")
     * @ORM\JoinColumn(name="remote_desktops_id", referencedColumnName="id")
     */
    protected $remoteDesktop;

    /**
     * @var int
     * @ORM\Column(name="status", type="smallint")
     */
    protected $status;

    /**
     * @var int
     * @ORM\Column(name="runstatus", type="smallint")
     */
    protected $runstatus;

    /**
     * @var string
     * @ORM\Column(name="flavor_internal_name", type="string", length=128)
     */
    protected $flavorInternalName;

    /**
     * @var string
     * @ORM\Column(name="image_internal_name", type="string", length=128)
     */
    protected $imageInternalName;

    /**
     * @var string
     * @ORM\Column(name="region_internal_name", type="string", length=128)
     */
    protected $regionInternalName;

    /**
     * @var int
     * @ORM\Column(name="root_volume_size", type="integer")
     */
    protected $rootVolumeSize;

    /**
     * @var int
     * @ORM\Column(name="additional_volume_size", type="integer")
     */
    protected $additionalVolumeSize;

    /**
     * @var string
     * @ORM\Column(name="public_address", type="string", length=128, nullable=true)
     */
    protected $publicAddress;

    /**
     * @var string
     * @ORM\Column(name="admin_password", type="string", length=128, nullable=true)
     */
    protected $adminPassword;

    /**
     * @var \DateTime $scheduleForStopAt
     *
     * @ORM\Column(name="schedule_for_stop_at", type="datetime", nullable=true)
     */
    protected $scheduleForStopAt;


    public function __construct()
    {
        $this->setStatus(self::STATUS_IN_USE);
        $this->runstatus = self::RUNSTATUS_NEVER_SET;
    }

    public function setId(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setRemoteDesktop(RemoteDesktop $remoteDesktop)
    {
        $this->remoteDesktop = $remoteDesktop;
    }

    public function getRemoteDesktop(): RemoteDesktop
    {
        return $this->remoteDesktop;
    }

    public function setStatus(int $status)
    {
        if ($status < self::STATUS_IN_USE || $status > self::STATUS_ARCHIVED) {
            throw new \Exception('Status ' . $status . ' is invalid');
        }
        $this->status = $status;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setRunstatus(int $runstatus)
    {
        if ($runstatus < self::RUNSTATUS_SCHEDULED_FOR_LAUNCH || $runstatus > self::RUNSTATUS_REBOOTING) {
            throw new \Exception('Runstatus ' . $runstatus . ' is invalid');
        }

        // We are billing as fair as possible: billing of provisioning of instances only starts as soon as the machine
        // is launched for the very first time, and also billing of usage only when we are through with booting and
        // the user can actually use the system. Rebooting doesn't actually do anything in terms of billing - it's
        // as if the system is simply running through (which is actually true for the VM.
        if ($runstatus === self::RUNSTATUS_RUNNING && $this->getRunstatus() !== self::RUNSTATUS_REBOOTING) {
            $remoteDesktopRelevantForBillingEvents = $this->remoteDesktop->getRemoteDesktopRelevantForBillingEvents();
            if ($remoteDesktopRelevantForBillingEvents->count() === 0) {
                $remoteDesktopRelevantForBillingEvent = new RemoteDesktopRelevantForBillingEvent(
                    $this->remoteDesktop,
                    RemoteDesktopRelevantForBillingEvent::EVENT_TYPE_DESKTOP_WAS_PROVISIONED_FOR_USER,
                    DateTimeUtility::createDateTime('now')
                );
                $this->remoteDesktop->addRemoteDesktopRelevantForBillingEvent($remoteDesktopRelevantForBillingEvent);
            }

            $remoteDesktopRelevantForBillingEvent = new RemoteDesktopRelevantForBillingEvent(
                $this->remoteDesktop,
                RemoteDesktopRelevantForBillingEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER,
                DateTimeUtility::createDateTime('now')
            );
            $this->remoteDesktop->addRemoteDesktopRelevantForBillingEvent($remoteDesktopRelevantForBillingEvent);

            // Auto schedule for stop in 3 hours and 59 minutes (14340 seconds)
            $this->setScheduleForStopAt(DateTimeUtility::createDateTime()->add(new \DateInterval('PT14340S')));
        }

        if ($runstatus === self::RUNSTATUS_SCHEDULED_FOR_TERMINATION) {
            // In case we schedule a running instance for termination (and not a stopped one), we need to track the
            // fact that the desktop becomes unavailable to the user without going through the stopped phase first
            if ($this->getRunstatus() === self::RUNSTATUS_RUNNING) {
                $remoteDesktopRelevantForBillingEvent = new RemoteDesktopRelevantForBillingEvent(
                    $this->remoteDesktop,
                    RemoteDesktopRelevantForBillingEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER,
                    DateTimeUtility::createDateTime('now')
                );
                $this->remoteDesktop->addRemoteDesktopRelevantForBillingEvent($remoteDesktopRelevantForBillingEvent);
            }

            // In any case, provisioning billing stops once a machine is scheduled for termination
            $remoteDesktopRelevantForBillingEvent = new RemoteDesktopRelevantForBillingEvent(
                $this->remoteDesktop,
                RemoteDesktopRelevantForBillingEvent::EVENT_TYPE_DESKTOP_WAS_UNPROVISIONED_FOR_USER,
                DateTimeUtility::createDateTime('now')
            );
            $this->remoteDesktop->addRemoteDesktopRelevantForBillingEvent($remoteDesktopRelevantForBillingEvent);
        }

        // We stop usage billing as soon as the user don't want the system anymore
        if ($runstatus === self::RUNSTATUS_SCHEDULED_FOR_STOP) {
            $remoteDesktopRelevantForBillingEvent = new RemoteDesktopRelevantForBillingEvent(
                $this->remoteDesktop,
                RemoteDesktopRelevantForBillingEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER,
                DateTimeUtility::createDateTime('now')
            );
            $this->remoteDesktop->addRemoteDesktopRelevantForBillingEvent($remoteDesktopRelevantForBillingEvent);
        }

        // We do not track reboots as events which make the desktop unavailable, as AWS also doesn't do this.

        $this->runstatus = $runstatus;
    }

    public function getRunstatus(): int
    {
        return $this->runstatus;
    }

    public function setFlavor(Flavor $flavor)
    {
        $this->flavorInternalName = $flavor->getInternalName();
    }

    public function getFlavor(): Flavor
    {
        return $this->getCloudInstanceProvider()->getFlavorByInternalName($this->flavorInternalName);
    }

    public function setImage(Image $image)
    {
        $this->imageInternalName = $image->getInternalName();
    }

    public function getImage(): Image
    {
        return $this->getCloudInstanceProvider()->getImageByInternalName($this->imageInternalName);
    }

    public function setRegion(Region $region)
    {
        $this->regionInternalName = $region->getInternalName();
    }

    public function getRegion(): Region
    {
        return $this->getCloudInstanceProvider()->getRegionByInternalName($this->regionInternalName);
    }

    public function setRootVolumeSize(int $sizeInGB)
    {
        $this->rootVolumeSize = $sizeInGB;
    }

    public function getRootVolumeSize(): int
    {
        if (is_null($this->rootVolumeSize)) {
            return 0;
        } else {
            return $this->rootVolumeSize;
        }
    }

    public function setAdditionalVolumeSize(int $sizeInGB)
    {
        $this->additionalVolumeSize = $sizeInGB;
    }

    public function getAdditionalVolumeSize() : int
    {
        if (is_null($this->additionalVolumeSize)) {
            return 0;
        } else {
            return $this->additionalVolumeSize;
        }
    }

    public function getHourlyUsageCosts(): float
    {
        return $this
            ->getCloudInstanceProvider()
            ->getHourlyUsageCostsForFlavorImageRegionCombination(
                $this->getFlavor(),
                $this->getImage(),
                $this->getRegion()
            );
    }

    public function getHourlyProvisioningCosts(): float
    {
        return $this
            ->getCloudInstanceProvider()
            ->getHourlyProvisioningCostsForFlavorImageRegionVolumeSizesCombination(
                $this->getFlavor(),
                $this->getImage(),
                $this->getRegion(),
                $this->getRootVolumeSize(),
                $this->getAdditionalVolumeSize()
            );
    }

    public static function getStatusName(int $status) : string {
        switch ($status) {
            case self::STATUS_IN_USE:
                return 'in use';
                break;
            case self::STATUS_ARCHIVED:
                return 'archived';
                break;
        }
        return 'Could not resolve status to name';
    }

    public static function getRunstatusName(int $runstatus) : string {
        switch ($runstatus) {
            case self::RUNSTATUS_SCHEDULED_FOR_LAUNCH:
                return 'scheduled for launch';
                break;
            case self::RUNSTATUS_LAUNCHING:
                return 'launching';
                break;
            case self::RUNSTATUS_RUNNING:
                return 'running';
                break;
            case self::RUNSTATUS_SCHEDULED_FOR_STOP:
                return 'scheduled for stop';
                break;
            case self::RUNSTATUS_STOPPING:
                return 'stopping';
                break;
            case self::RUNSTATUS_STOPPED:
                return 'stopped';
                break;
            case self::RUNSTATUS_SCHEDULED_FOR_START:
                return 'scheduled for start';
                break;
            case self::RUNSTATUS_STARTING;
                return 'starting';
                break;
            case self::RUNSTATUS_SCHEDULED_FOR_TERMINATION:
                return 'scheduled for termination';
                break;
            case self::RUNSTATUS_TERMINATING:
                return 'terminating';
                break;
            case self::RUNSTATUS_TERMINATED:
                return 'terminated';
                break;

        }
        return 'Could not resolve runstatus to name';
    }

    public function setPublicAddress(string $addr)
    {
        $this->publicAddress = $addr;
    }

    public function getPublicAddress(): string
    {
        return (string)$this->publicAddress;
    }

    public function setAdminPassword(string $password)
    {
        $cryptor = new Cryptor();
        $this->adminPassword = $cryptor->encryptString(
            $password,
            CloudInstance::ADMIN_PASSWORD_ENCRYPTION_KEY
        );
    }

    public function getAdminPassword(): string
    {
        if ($this->adminPassword != '') {
            $cryptor = new Cryptor();
            return $cryptor->decryptString(
                $this->adminPassword,
                CloudInstance::ADMIN_PASSWORD_ENCRYPTION_KEY
            );
        } else {
            return '';
        }
    }

    public function setScheduleForStopAt(\DateTime $dateTime)
    {
        if ($dateTime->getTimezone()->getName() !== 'UTC') {
            throw new \Exception('Provided time zone is not UTC.');
        }
        $this->scheduleForStopAt = $dateTime;
    }

    /**
     * @return null|\DateTime
     * @throws \Exception
     */
    public function getScheduleForStopAt()
    {
        if (!is_null($this->scheduleForStopAt)) {
            if ($this->scheduleForStopAt->getTimezone()->getName() !== 'UTC') {
                throw new \Exception('Stored time zone is not UTC, but ' . $this->scheduleForStopAt->getTimezone()->getName());
            }
            return clone($this->scheduleForStopAt);
        } else {
            return null;
        }
    }

}
