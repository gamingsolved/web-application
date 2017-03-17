<?php

namespace AppBundle\Entity\CloudInstance;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="aws_cloud_instances")
 */
class AwsCloudInstance extends CloudInstance
{
    const CLOUD_INSTANCE_PROVIDER = CloudInstance::CLOUD_INSTANCE_PROVIDER_AWS;

    /**
     * @var string
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\RemoteDesktop", inversedBy="aws_cart_instances")
     * @ORM\JoinColumn(name="remote_desktops_id", referencedColumnName="id")
     */
    protected $remoteDesktop;
}
