<?php

namespace AppBundle\Controller;

use AppBundle\Form\Type\RemoteDesktopType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class RemoteDesktopController extends Controller
{
    public function newAction()
    {
        $form = $this->createForm(RemoteDesktopType::class);
        return $this->render('AppBundle:remoteDesktop:new.html.twig', array('form' => $form->createView()));
    }
}
