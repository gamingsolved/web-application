<?php

namespace AppBundle\Entity\CloudInstance;

use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProviderInterface;
use AppBundle\Entity\CloudInstanceProvider\PaperspaceCloudInstanceProvider;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="paperspace_cloud_instances",indexes={@ORM\Index(name="ps_instance_id_index", columns={"ps_instance_id"})})
 */
class PaperspaceCloudInstance extends CloudInstance
{
    /**
     * @var RemoteDesktop
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\RemoteDesktop\RemoteDesktop", inversedBy="paperspaceCloudInstances")
     * @ORM\JoinColumn(name="remote_desktops_id", referencedColumnName="id")
     */
    protected $remoteDesktop;

    /**
     * @var string
     * @ORM\Column(name="ps_instance_id", type="string", length=128, nullable=true)
     */
    protected $psInstanceId;

    public function getCloudInstanceProvider(): CloudInstanceProviderInterface
    {
        return new PaperspaceCloudInstanceProvider();
    }

    public function getProviderInstanceId() : string
    {
        return $this->getPsInstanceId();
    }

    public function setPsInstanceId(string $id)
    {
        $this->psInstanceId = $id;
    }

    public function getPsInstanceId() : string
    {
        return (string)$this->psInstanceId;
    }
}
