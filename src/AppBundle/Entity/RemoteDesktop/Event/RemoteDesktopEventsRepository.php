<?php

namespace AppBundle\Entity\RemoteDesktop\Event;

use AppBundle\Entity\RemoteDesktop\RemoteDesktop;

interface RemoteDesktopEventsRepositoryInterface
{
    public function hasEvents(RemoteDesktop $remoteDesktop) : bool;

    public function addEvent() : bool;
}
