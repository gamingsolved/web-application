<?php

namespace AppBundle\Entity\RemoteDesktop;

use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Doctrine\DBAL\Types\Type;

Type::addType('RemoteDesktopKindType', 'AppBundle\Entity\RemoteDesktop\RemoteDesktopKindType');

/**
 * @ORM\Entity
 * @ORM\Table(name="remote_desktops")
 */
class RemoteDesktop
{
    const STREAMING_CLIENT_CGX = 0;

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
     * @var boolean
     * @ORM\Column(name="is_running", type="boolean", nullable=false)
     */
    private $isRunning = false;

    /**
     * @var int
     * @ORM\Column(name="streaming_client", type="smallint", nullable=false)
     */
    private $streamingClient = self::STREAMING_CLIENT_CGX;

    /**
     * @var int
     * @ORM\Column(name="cloud_instance_provider_id", type="smallint", nullable=false)
     */
    private $cloudInstanceProviderId = CloudInstanceProvider::CLOUD_INSTANCE_PROVIDER_AWS_ID;

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

    /**
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param RemoteDesktopKind $kind
     */
    public function setKind(string $kind)
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

    /**
     * @param CloudInstance $cloudInstance
     * @return void
     * @throws \Exception
     */
    public function addCloudInstance(CloudInstance $cloudInstance)
    {
        if ($this->cloudInstanceProviderId == CloudInstanceProvider::CLOUD_INSTANCE_PROVIDER_AWS_ID) {
            $cloudInstance->setRemoteDesktop($this);
            $this->awsCloudInstances[] = $cloudInstance;
        } else {
            throw new \Exception();
        }
    }

    /**
     * @return ArrayCollection|CloudInstance
     * @throws \Exception
     */
    public function getCloudInstances()
    {
        if ($this->cloudInstanceProviderId == CloudInstanceProvider::CLOUD_INSTANCE_PROVIDER_AWS_ID) {
            return $this->awsCloudInstances;
        } else {
            throw new \Exception();
        }
    }

}
