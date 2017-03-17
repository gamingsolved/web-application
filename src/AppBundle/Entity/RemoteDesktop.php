<?php

namespace AppBundle\Entity;

use AppBundle\Entity\CloudInstance\CloudInstance;
use Doctrine\ORM\Mapping as ORM;

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
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\User", inversedBy="desktops")
     * @ORM\JoinColumn(name="users_id", referencedColumnName="id")
     */
    private $user;

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
     * @ORM\Column(name="cloud_instance_provider", type="smallint", nullable=false)
     */
    private $cloudInstanceProvider = CloudInstance::CLOUD_INSTANCE_PROVIDER_AWS;

    /**
     * @var \AppBundle\Entity\CloudInstance\AwsCloudInstance
     * @ORM\OneToOne(targetEntity="\AppBundle\Entity\CloudInstance\AwsCloudInstance", mappedBy="remote_desktops")
     */
    private $awsCloudInstance;

    /**
     * @return CloudInstance
     */
    public function getInstance()
    {
        if ($this->cloudInstanceProvider == CloudInstance::CLOUD_INSTANCE_PROVIDER_AWS) {
            return $this->awsCloudInstance;
        } else {
            throw new \Exception();
        }
    }
}
