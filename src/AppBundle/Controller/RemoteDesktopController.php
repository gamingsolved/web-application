<?php

namespace AppBundle\Controller;

use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Factory\RemoteDesktopFactory;
use AppBundle\Form\Type\RemoteDesktopType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RemoteDesktopController extends Controller
{
    public function indexAction()
    {
        $user = $this->getUser();

        $em = $this->getDoctrine()->getManager();
        $rdRepo = $em->getRepository(RemoteDesktop::class);

        $remoteDesktops = $rdRepo->findBy(['user' => $user]);

        return $this->render('AppBundle:remoteDesktop:index.html.twig', ['remoteDesktops' => $remoteDesktops]);
    }

    public function newAction(Request $request)
    {
        $user = $this->getUser();

        $form = $this->createForm(RemoteDesktopType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $remoteDesktop = RemoteDesktopFactory::createFromForm($form, $user);
            $em = $this->getDoctrine()->getManager();
            $em->persist($remoteDesktop);
            $em->flush();

            return $this->redirectToRoute('cloudinstances.new', ['remoteDesktop' => $remoteDesktop->getId()], Response::HTTP_CREATED);
        } else {
            return $this->render('AppBundle:remoteDesktop:new.html.twig', ['form' => $form->createView()]);
        }

    }
}
