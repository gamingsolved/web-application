<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AboutController extends Controller
{
    public function howBillingWorksAction()
    {
        return $this->render('AppBundle:about:how-billing-works.html.twig');
    }

    public function privacyPolicyAction()
    {
        return $this->render('AppBundle:about:privacy-policy.html.twig');
    }

    public function imprintAction()
    {
        return $this->render('AppBundle:about:imprint.html.twig');
    }
}
