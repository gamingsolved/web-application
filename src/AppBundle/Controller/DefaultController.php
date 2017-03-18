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
        if (substr($request->getRequestUri(), 0, 7) === '/en/en/') {
            return $this->render('default/notfound.html.twig');
        }

        $preferred = $request->getPreferredLanguage(['fr', 'de', 'en']);

        $redirectTo = '/' .$preferred . $request->getRequestUri();

        return $this->redirect($redirectTo);
    }

    public function indexAction(Request $request)
    {
        return $this->render('default/index.html.twig');
    }

    public function logoutSuccessfulAction()
    {
        $this->addFlash('success', $this->get('translator')->trans('logoutSuccessful.message'));
        return $this->render('default/logoutSuccessful.html.twig');
    }
}
