<?php

namespace AppBundle\Entity\CloudInstanceProvider\ProviderElement;

use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProviderInterface;

abstract class ProviderElement
{
    protected $cloudInstanceProvider;
    protected $internalName;
    protected $humanName;

    /**
     * @param CloudInstanceProviderInterface $cloudInstanceProvider
     * @param string $internalName
     * @param string $humanName
     */
    public function __construct(CloudInstanceProviderInterface $cloudInstanceProvider, string $internalName, string $humanName)
    {
        $this->cloudInstanceProvider = $cloudInstanceProvider;
        $this->internalName = $internalName;
        $this->humanName = $humanName;
    }

    public function getInternalName() : string
    {
        return $this->getInternalName();
    }

    public function getHumanName() : string
    {
        return $this->getHumanName();
    }
}
