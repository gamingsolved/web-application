<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * The special root "/" (without a langauge tag) ends up here.
     *
     * Because we want users to always be on a language path, we redirect
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function rootAction()
    {
        return $this->redirect('/en/');
    }

    public function indexAction(Request $request)
    {
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ]);
    }
}
