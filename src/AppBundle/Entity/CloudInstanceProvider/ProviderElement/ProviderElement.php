<?php

namespace AppBundle\Entity\CloudInstanceProvider\ProviderElement;

use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProviderInterface;

abstract class ProviderElement
{
    protected $cloudInstanceProvider;
    protected $internalName;
    protected $humanName;
    protected $isCurrentlyChoosable;

    public function __construct(CloudInstanceProviderInterface $cloudInstanceProvider, string $internalName, string $humanName, bool $isCurrentlyChoosable = true)
    {
        $this->cloudInstanceProvider = $cloudInstanceProvider;
        $this->internalName = $internalName;
        $this->humanName = $humanName;
        $this->isCurrentlyChoosable = $isCurrentlyChoosable;
    }

    public function getInternalName() : string
    {
        return $this->internalName;
    }

    public function getHumanName() : string
    {
        return $this->humanName;
    }

    public function getIsCurrentlyChoosable() : bool
    {
        return $this->isCurrentlyChoosable;
    }
}
