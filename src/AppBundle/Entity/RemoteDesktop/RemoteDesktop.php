<?php

namespace AppBundle\Entity\RemoteDesktop;

use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstanceInterface;
use AppBundle\Entity\CloudInstanceProvider\AwsCloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProviderInterface;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Flavor;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Image;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use AppBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\PersistentCollection;

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
    const STATUS_LAUNCHING = 1;
    const STATUS_RUNNING = 2;
    const STATUS_STOPPED = 3;

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
     * @var ArrayCollection|\AppBundle\Entity\CloudInstance\AwsCloudInstance
     * @ORM\OneToMany(targetEntity="\AppBundle\Entity\CloudInstance\AwsCloudInstance", mappedBy="remoteDesktop", cascade="all")
     */
    private $awsCloudInstances;

    public function __construct() {
        $this->awsCloudInstances = new ArrayCollection();
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

    public function setCloudInstanceProvider(CloudInstanceProviderInterface $cloudInstanceProvider)
    {
        $this->cloudInstanceProvider = $cloudInstanceProvider;
    }

    public function getCloudInstanceProvider() : CloudInstanceProviderInterface
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
        } else {
            throw new \Exception(
                'Cannot add cloud instance of class ' . get_class($cloudInstance) .
                ' because this remote desktop is configured for cloud instance provider ' . get_class($this->cloudInstanceProvider)
            );
        }
    }

    /**
     * @return ArrayCollection|CloudInstance
     */
    public function getCloudInstances() : PersistentCollection
    {
        return $this->awsCloudInstances;
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
                    $status = self::STATUS_LAUNCHING;
                    break;
                case CloudInstance::RUNSTATUS_RUNNING:
                    $status = self::STATUS_RUNNING;
                    break;
                case CloudInstance::RUNSTATUS_SCHEDULED_FOR_SHUTDOWN:
                case CloudInstance::RUNSTATUS_SHUTTING_DOWN:
                case CloudInstance::RUNSTATUS_SHUT_DOWN:
                    $status = self::STATUS_STOPPED;
                    break;
                default:
                    throw new \Exception('Unexpected cloud instance runstatus ' . $activeCloudInstance->getRunstatus());

            }
        }

        return $status;
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

    /**
     * @throws \Exception
     */
    protected function getActiveCloudInstance() : CloudInstanceInterface {
        $cloudInstances = $this->getCloudInstances();
        /** @var CloudInstance $cloudInstance */
        foreach ($cloudInstances as $cloudInstance) {
            if ($cloudInstance->getStatus() == CloudInstance::STATUS_IN_USE) {
                return $cloudInstance;
            }
        }
        throw new \Exception('Could not find the active instance for remote desktop ' . $this->getId());
    }

}
