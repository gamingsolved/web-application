<?php

namespace AppBundle\Controller;

use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\RemoteDesktop;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $u = $this->getUser();

        $em = $this->getDoctrine()->getManager();
        $i1 = new AwsCloudInstance();
        $i2 = new AwsCloudInstance();
        $d = new RemoteDesktop();
        $d->addCloudInstance($i1);
        $d->addCloudInstance($i2);
        $d->setUser($u);
        $em->persist($d);
        $em->flush();

        $dRepo = $em->getRepository('AppBundle\Entity\RemoteDesktop');
        $d = $dRepo->find($d->getId());
        $is = $d->getCloudInstances();

        $logger = $this->get('logger');
        $logger->info(print_r($is[0]->getId(), true));
        $logger->info(print_r($is[1]->getId(), true));

        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ]);
    }
}
