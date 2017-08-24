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
     * @var string
     * @ORM\Column(name="ps_instance_id", type="string", length=128, nullable=true)
     */
    protected $psInstanceId;

    protected $usageCostsInterval = RemoteDesktop::COSTS_INTERVAL_HOURLY;
    protected $provisioningCostsInterval = RemoteDesktop::COSTS_INTERVAL_MONTHLY;

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
