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
            return $this->render('AppBundle:default:notfound.html.twig');
        }

        $preferred = $request->getPreferredLanguage(['en']);

        $redirectTo = '/' .$preferred . $request->getRequestUri();

        return $this->redirect($redirectTo);
    }

    public function indexAction(Request $request)
    {
        $user = $this->getUser();

        if (!is_null($user)) {
            return $this->redirectToRoute('remotedesktops.index');
        } else {
            return $this->render('AppBundle:default:index.html.twig');
        }
    }

    public function indexLinuxAction()
    {
        return $this->render('AppBundle:default:linux.html.twig');
    }

    public function indexMacAction()
    {
        return $this->render('AppBundle:default:mac.html.twig');
    }

    public function logoutSuccessfulAction()
    {
        $this->addFlash('success', $this->get('translator')->trans('logoutSuccessful.message'));
        return $this->render('AppBundle:default:logoutSuccessful.html.twig');
    }
}
