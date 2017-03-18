<?php

namespace AppBundle\Entity\CloudInstance;

use AppBundle\Entity\RemoteDesktop\RemoteDesktop;

interface CloudInstanceInterface
{
    public function getCloudInstanceProvider();
    public function setRemoteDesktop(RemoteDesktop $remoteDesktop);
}

abstract class CloudInstance implements CloudInstanceInterface
{

}
