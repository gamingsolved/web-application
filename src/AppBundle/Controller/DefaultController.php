<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * We end up here if no other route matches
     *
     * Because we want users to always be on a language path, we redirect
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function catchallAction(Request $request)
    {
        $redirectTo = '/en' . $request->getRequestUri();
        return $this->redirect($redirectTo);
    }

    public function indexAction(Request $request)
    {
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ]);
    }
}
