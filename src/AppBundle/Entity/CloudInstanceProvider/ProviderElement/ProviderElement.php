<?php

namespace AppBundle\Entity\CloudInstanceProvider\ProviderElement;

abstract class ProviderElement
{
    protected $cloudInstanceProvider;
    protected $internalName;
    protected $humanName;

    /**
     * @param CloudInstanceProvider $cloudInstanceProvider
     * @param string $internalName
     * @param string $humanName
     */
    public function __construct(CloudInstanceProvider $cloudInstanceProvider, string $internalName, string $humanName)
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
