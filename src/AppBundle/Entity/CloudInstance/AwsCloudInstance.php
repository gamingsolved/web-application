<?php

namespace AppBundle\Entity\CloudInstance;

use AppBundle\Entity\CloudInstanceProvider\AwsCloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProviderInterface;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Flavor;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Image;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="aws_cloud_instances")
 */
class AwsCloudInstance extends CloudInstance
{
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
     * @var string
     * @ORM\Column(name="flavor_internal_name", type="string", length=128)
     */
    protected $flavorInternalName;

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
     * @ORM\Column(name="image_internal_name", type="string", length=128)
     */
    protected $imageInternalName;

    /**
     * @var string
     * @ORM\Column(name="region_internal_name", type="string", length=128)
     */
    protected $regionInternalName;

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


    // The following fields are AWS specific

    /**
     * @var string
     * @ORM\Column(name="ec2_instance_id", type="string", length=128, nullable=true)
     */
    protected $ec2InstanceId;


    public function __construct()
    {
        $this->awsCloudInstanceProvider = new AwsCloudInstanceProvider();
    }

    public function getId() : string
    {
        return $this->id;
    }

    public function setRemoteDesktop(RemoteDesktop $remoteDesktop)
    {
        $this->remoteDesktop = $remoteDesktop;
    }

    public function getCloudInstanceProvider() : CloudInstanceProviderInterface
    {
        return new AwsCloudInstanceProvider();
    }

    public function setRegionInternalName(string $regionInternalName)
    {
        $this->regionInternalName = $regionInternalName;
    }

    public function getRegionInternalName() : string
    {
        return $this->regionInternalName;
    }

    public function setStatus(int $status)
    {
        if ($status < self::STATUS_IN_USE || $status > self::STATUS_ARCHIVED) {
            throw new \Exception('Status ' . $status . ' is invalid');
        }
        $this->status = $status;
    }

    public function getStatus() : int
    {
        return $this->status;
    }

    public function setRunstatus(int $runstatus)
    {
        if ($runstatus < self::RUNSTATUS_SCHEDULED_FOR_LAUNCH || $runstatus > self::RUNSTATUS_SHUT_DOWN) {
            throw new \Exception('Runstatus ' . $runstatus . ' is invalid');
        }
        $this->runstatus = $runstatus;
    }

    public function getRunstatus() : int
    {
        return $this->runstatus;
    }

    public function setFlavor(Flavor $flavor)
    {
        $this->flavorInternalName = $flavor->getInternalName();
    }

    public function getFlavor() : Flavor
    {
        return $this->getCloudInstanceProvider()->getFlavorByInternalName($this->flavorInternalName);
    }

    public function setImage(Image $image)
    {
        $this->imageInternalName = $image->getInternalName();
    }

    public function getImage() : Image
    {
        return $this->getCloudInstanceProvider()->getImageByInternalName($this->imageInternalName);
    }

    public function setRegion(Region $region)
    {
        $this->regionInternalName = $region->getInternalName();
    }

    public function getRegion() : Region
    {
        return $this->getCloudInstanceProvider()->getRegionByInternalName($this->regionInternalName);
    }

    public function setPublicAddress(string $addr)
    {
        $this->publicAddress = $addr;
    }

    public function getPublicAddress() : string
    {
        return (string)$this->publicAddress;
    }

    public function setAdminPassword(string $password)
    {
        $this->adminPassword = $password;
    }

    public function getAdminPassword() : string
    {
        return (string)$this->adminPassword;
    }


    // The following are AWS specific

    public function setEc2InstanceId(string $id)
    {
        $this->ec2InstanceId = $id;
    }

    public function getEc2InstanceId() : string
    {
        return (string)$this->ec2InstanceId;
    }
}
