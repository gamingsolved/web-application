<?php

namespace AppBundle\Entity\RemoteDesktop\Event;

use AppBundle\Entity\RemoteDesktop\RemoteDesktop;

interface RemoteDesktopEventsRepositoryInterface
{
    public function countForRemoteDesktop(RemoteDesktop $remoteDesktop) : int;
}
