<?php

namespace AppBundle\Controller;

use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Factory\RemoteDesktopFactory;
use AppBundle\Form\Type\RemoteDesktopType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CloudInstanceController extends Controller
{
    /**
     * @ParamConverter("post", class="AppBundle:RemoteDesktop")
     */
    public function newAction(RemoteDesktop $remoteDesktop, Request $request)
    {
        $user = $this->getUser();

        $logger = $this->get('logger');

    }
}
