<?php

namespace AppBundle\Entity\RemoteDesktop;

use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstanceProvider\AwsCloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProviderInterface;
use AppBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
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
    public function getCloudInstances()
    {
        return $this->awsCloudInstances;
    }

}
