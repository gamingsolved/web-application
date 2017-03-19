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
     * @var string
     * @ORM\Column(name="image_internal_name", type="string", length=128)
     */
    protected $imageInternalName;

    /**
     * @var string
     * @ORM\Column(name="region_internal_name", type="string", length=128)
     */
    protected $regionInternalName;

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
        return $this->awsCloudInstanceProvider;
    }

    public function setRegionInternalName(string $regionInternalName)
    {
        $this->regionInternalName = $regionInternalName;
    }

    public function getRegionInternalName() : string
    {
        return $this->regionInternalName;
    }

    public function setFlavor(Flavor $flavor)
    {
        $this->flavorInternalName = $flavor->getInternalName();
    }

    public function getFlavor() : Flavor
    {
        return $this->getCloudInstanceProvider()->getFlavorByInternalName($this->getFlavorInternalName());
    }

    public function setImage(Image $image)
    {
        $this->imageInternalName = $image->getInternalName();
    }

    public function getImage() : Image
    {
        return $this->getCloudInstanceProvider()->getImageByInternalName($this->getImageInternalName());
    }


    public function setRegion(Region $region)
    {
        $this->regionInternalName = $region->getInternalName();
    }

    public function getRegion() : Region
    {
        return $this->getCloudInstanceProvider()->getRegionByInternalName($this->getRegionInternalName());
    }
}
