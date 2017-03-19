<?php

namespace AppBundle\Factory;

use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Entity\RemoteDesktop\RemoteDesktopKind;
use AppBundle\Entity\User;
use Symfony\Component\Form\Form;

abstract class RemoteDesktopFactory
{
    public static function createFromForm(Form $form, User $user) : RemoteDesktop
    {
        $remoteDesktop = new RemoteDesktop();
        $remoteDesktop->setUser($user);
        $remoteDesktop->setTitle($form->get('title')->getData());
        $remoteDesktop->setKind(
            RemoteDesktopKind::createRemoteDesktopKind(
                $form->get('kind')->getData()
            )
        );
        $remoteDesktop->setCloudInstanceProvider($remoteDesktop->getKind()->getCloudInstanceProvider());
        return $remoteDesktop;
    }
}
