<?php

namespace AppBundle\Entity\CloudInstance;

use AppBundle\Entity\CloudInstanceProvider\AwsCloudInstanceProvider;
use AppBundle\Entity\RemoteDesktop;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="aws_cloud_instances")
 */
class AwsCloudInstance extends CloudInstance
{
    /**
     * @var AwsCloudInstanceProvider
     */
    protected $awsCloudInstanceProvider;

    /**
     * @var string
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var RemoteDesktop
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\RemoteDesktop", inversedBy="awsCloudInstances")
     * @ORM\JoinColumn(name="remote_desktops_id", referencedColumnName="id")
     */
    protected $remoteDesktop;

    public function __construct()
    {
        $this->awsCloudInstanceProvider = new AwsCloudInstanceProvider();
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param RemoteDesktop $remoteDesktop
     */
    public function setRemoteDesktop(RemoteDesktop $remoteDesktop)
    {
        $this->remoteDesktop = $remoteDesktop;
    }

    /**
     * @return AwsCloudInstanceProvider
     */
    public function getCloudInstanceProvider()
    {
        return $this->awsCloudInstanceProvider;
    }
}
