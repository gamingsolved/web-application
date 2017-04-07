<?php

namespace AppBundle\Entity\CloudInstance;

use AppBundle\Entity\CloudInstanceProvider\AwsCloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProviderInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="aws_cloud_instances",indexes={@ORM\Index(name="ec2_instance_id_index", columns={"ec2_instance_id"})})
 */
class AwsCloudInstance extends CloudInstance
{
    // The following fields are AWS specific

    /**
     * @var string
     * @ORM\Column(name="ec2_instance_id", type="string", length=128, nullable=true)
     */
    protected $ec2InstanceId;


    public function getCloudInstanceProvider(): CloudInstanceProviderInterface
    {
        return new AwsCloudInstanceProvider();
    }

    public function getProviderInstanceId() : string
    {
        return $this->getEc2InstanceId();
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
