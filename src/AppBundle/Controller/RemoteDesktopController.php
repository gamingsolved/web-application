<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Billing\AccountMovement;
use AppBundle\Entity\Billing\AccountMovementRepository;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Factory\RemoteDesktopFactory;
use AppBundle\Form\Type\RemoteDesktopType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class RemoteDesktopController extends Controller
{
    public function indexAction(Request $request)
    {
        $user = $this->getUser();

        $em = $this->getDoctrine()->getManager();
        /** @var EntityRepository $remoteDesktopRepo */
        $remoteDesktopRepo = $em->getRepository(RemoteDesktop::class);

        $remoteDesktops = $remoteDesktopRepo->findBy(['user' => $user]);

        $remoteDesktopsSorted = [];

        // List Ready to use and booting instances first
        /** @var RemoteDesktop $remoteDesktop */
        foreach ($remoteDesktops as $remoteDesktop) {
            if ($remoteDesktop->getStatus() === RemoteDesktop::STATUS_BOOTING) {
                $remoteDesktopsSorted[] = $remoteDesktop;
            }
        }

        /** @var RemoteDesktop $remoteDesktop */
        foreach ($remoteDesktops as $remoteDesktop) {
            if ($remoteDesktop->getStatus() === RemoteDesktop::STATUS_READY_TO_USE) {
                $remoteDesktopsSorted[] = $remoteDesktop;
            }
        }

        /** @var RemoteDesktop $remoteDesktop */
        foreach ($remoteDesktops as $remoteDesktop) {
            if (   $remoteDesktop->getStatus() !== RemoteDesktop::STATUS_BOOTING
                && $remoteDesktop->getStatus() !== RemoteDesktop::STATUS_READY_TO_USE) {
                $remoteDesktopsSorted[] = $remoteDesktop;
            }
        }

        /** @var AccountMovementRepository $accountMovementRepo */
        $accountMovementRepo = $em->getRepository(AccountMovement::class);

        return $this->render(
            'AppBundle:remoteDesktop:index.html.twig',
            [
                'launcherHostname' => $request->getHost(),
                'launcherPort' => $request->getPort(),
                'launcherProtocol' => $request->getScheme(),
                'remoteDesktops' => $remoteDesktopsSorted,
                'currentAccountBalance' => $accountMovementRepo->getAccountBalanceForUser($user)
            ]
        );
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

            return $this->redirectToRoute('cloudinstances.new', ['remoteDesktop' => $remoteDesktop->getId()]);
        } else {
            return $this->render('AppBundle:remoteDesktop:new.html.twig', ['form' => $form->createView()]);
        }
    }

    /**
     * @ParamConverter("remoteDesktop", class="AppBundle:RemoteDesktop\RemoteDesktop")
     */
    public function statusAction(RemoteDesktop $remoteDesktop, Request $request)
    {
        $user = $this->getUser();

        if ($remoteDesktop->getUser()->getId() !== $user->getId()) {
            return $this->redirectToRoute('remotedesktops.index', [], Response::HTTP_FORBIDDEN);
        }

        return $this->json($remoteDesktop->getStatus());
    }

    /**
     * @ParamConverter("remoteDesktop", class="AppBundle:RemoteDesktop\RemoteDesktop")
     */
    public function stopAction(RemoteDesktop $remoteDesktop, Request $request)
    {
        $user = $this->getUser();

        if ($remoteDesktop->getUser()->getId() !== $user->getId()) {
            return $this->redirectToRoute('remotedesktops.index', [], Response::HTTP_FORBIDDEN);
        }

        $remoteDesktop->scheduleForStop();

        $em = $this->getDoctrine()->getManager();
        $em->persist($remoteDesktop);
        $em->flush();

        return $this->redirectToRoute('remotedesktops.index');
    }

    /**
     * @ParamConverter("remoteDesktop", class="AppBundle:RemoteDesktop\RemoteDesktop")
     */
    public function startAction(RemoteDesktop $remoteDesktop, Request $request)
    {
        $user = $this->getUser();

        if ($remoteDesktop->getUser()->getId() !== $user->getId()) {
            return $this->redirectToRoute('remotedesktops.index', [], Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        /** @var AccountMovementRepository $accountMovementRepository */
        $accountMovementRepository = $em->getRepository(AccountMovement::class);

        if ($remoteDesktop->getHourlyCosts() > $accountMovementRepository->getAccountBalanceForUser($user)) {
            return $this->render(
                'AppBundle:remoteDesktop:insufficientAccountBalance.html.twig',
                [
                    'hourlyCosts' => $remoteDesktop->getHourlyCosts(),
                    'currentAccountBalance' => $accountMovementRepository->getAccountBalanceForUser($user)
                ]
            );
        }

        $remoteDesktop->scheduleForStart();

        $em = $this->getDoctrine()->getManager();
        $em->persist($remoteDesktop);
        $em->flush();

        return $this->redirectToRoute('remotedesktops.index');
    }

    /**
     * @ParamConverter("remoteDesktop", class="AppBundle:RemoteDesktop\RemoteDesktop")
     */
    public function terminateAction(RemoteDesktop $remoteDesktop, Request $request)
    {
        $user = $this->getUser();

        if ($remoteDesktop->getUser()->getId() !== $user->getId()) {
            return $this->redirectToRoute('remotedesktops.index', [], Response::HTTP_FORBIDDEN);
        }

        $remoteDesktop->scheduleForTermination();

        $em = $this->getDoctrine()->getManager();
        $em->persist($remoteDesktop);
        $em->flush();

        return $this->redirectToRoute('remotedesktops.index');
    }

    /**
     * @ParamConverter("remoteDesktop", class="AppBundle:RemoteDesktop\RemoteDesktop")
     */
    public function updateScheduleForStopAtAction(RemoteDesktop $remoteDesktop, int $duration)
    {
        $user = $this->getUser();

        if ($remoteDesktop->getUser()->getId() !== $user->getId()) {
            return $this->redirectToRoute('remotedesktops.index', [], Response::HTTP_FORBIDDEN);
        }

        $remoteDesktop->scheduleForStopInSeconds($duration);

        $em = $this->getDoctrine()->getManager();
        $em->persist($remoteDesktop);
        $em->flush();

        return $this->redirectToRoute('remotedesktops.index');
    }

    /**
     * @ParamConverter("remoteDesktop", class="AppBundle:RemoteDesktop\RemoteDesktop")
     */
    public function serveSgxFileAction(RemoteDesktop $remoteDesktop, string $remoteDesktopIdHash, string $width, string $height)
    {
        if ($remoteDesktop->getIdHash() !== $remoteDesktopIdHash) {
            return $this->redirectToRoute('homepage', [], Response::HTTP_FORBIDDEN);
        }

        $response = $this->render(
            'AppBundle:remoteDesktop:sgxFile/tag.sgx.twig',
            [
                'ip'       => $remoteDesktop->getPublicAddress(),
                'width'    => $width,
                'height'   => $height,
                'key'      => $remoteDesktop->getId(),
                'password' => $remoteDesktop->getAdminPassword()
            ]
        );

        $response->headers->set('Content-Type', 'text/plain');

        return $response;
    }
}
