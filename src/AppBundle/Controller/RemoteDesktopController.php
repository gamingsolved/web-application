<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Billing\AccountMovement;
use AppBundle\Entity\Billing\AccountMovementRepository;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEvent;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Entity\RemoteDesktop\RemoteDesktopKind;
use AppBundle\Factory\RemoteDesktopFactory;
use AppBundle\Service\RemoteDesktopAutostopService;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
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

        $remoteDesktops = $remoteDesktopRepo->findBy(['user' => $user], ['title' => 'ASC']);

        $remoteDesktopsSorted = [];

        /** @var RemoteDesktop $remoteDesktop */
        foreach ($remoteDesktops as $remoteDesktop) {
            if ($remoteDesktop->getStatus() === RemoteDesktop::STATUS_BOOTING) {
                $remoteDesktopsSorted[] = $remoteDesktop;
            }
        }

        /** @var RemoteDesktop $remoteDesktop */
        foreach ($remoteDesktops as $remoteDesktop) {
            if ($remoteDesktop->getStatus() === RemoteDesktop::STATUS_STOPPING) {
                $remoteDesktopsSorted[] = $remoteDesktop;
            }
        }

        /** @var RemoteDesktop $remoteDesktop */
        foreach ($remoteDesktops as $remoteDesktop) {
            if ($remoteDesktop->getStatus() === RemoteDesktop::STATUS_TERMINATING) {
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
            if ($remoteDesktop->getStatus() === RemoteDesktop::STATUS_NEVER_LAUNCHED) {
                $remoteDesktopsSorted[] = $remoteDesktop;
            }
        }

        /** @var RemoteDesktop $remoteDesktop */
        foreach ($remoteDesktops as $remoteDesktop) {
            if ($remoteDesktop->getStatus() === RemoteDesktop::STATUS_STOPPED) {
                $remoteDesktopsSorted[] = $remoteDesktop;
            }
        }

        /** @var RemoteDesktop $remoteDesktop */
        foreach ($remoteDesktops as $remoteDesktop) {
            if ($remoteDesktop->getStatus() === RemoteDesktop::STATUS_TERMINATED) {
                $remoteDesktopsSorted[] = $remoteDesktop;
            }
        }

        $rdas = new RemoteDesktopAutostopService();
        /** @var RemoteDesktop $remoteDesktop */
        foreach ($remoteDesktopsSorted as $remoteDesktop) {
            if ($remoteDesktop->getStatus() == RemoteDesktop::STATUS_READY_TO_USE) {
                $remoteDesktop->setOptimalHourlyAutostopTimes(
                    $rdas->getOptimalHourlyAutostopTimesForRemoteDesktop(
                        $remoteDesktop,
                        $em->getRepository(RemoteDesktopEvent::class)
                    )
                );
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

        /** @var Translator $t */
        $t = $this->get('translator');

        $choices = [];

        $availableRemoteDesktopKinds = RemoteDesktopKind::getAvailableKinds();
        /** @var RemoteDesktopKind $remoteDesktopKind */
        foreach ($availableRemoteDesktopKinds as $remoteDesktopKind) {
            $choices[
                $t->trans((string)$remoteDesktopKind)
                . ' — ' . $remoteDesktopKind->getFlavor()->getHumanName()
                . ' — $' . $remoteDesktopKind->getMaximumHourlyCosts()
                . '/h'
            ] = $remoteDesktopKind->getIdentifier();
        }

        $form = $this->createFormBuilder()->getForm();
        $form
            ->add('title', TextType::class, ['label' => 'remoteDesktop.new.form.title_label'])
            ->add(
                'kind',
                ChoiceType::class,
                [
                    'choices' => $choices,
                    'expanded' => true,
                    'multiple' => false,
                    'label' => 'remoteDesktop.new.form.kind_label'
                ]
            )
            ->add('send', SubmitType::class, ['label' => 'remoteDesktop.new.form.submit_label', 'attr' => ['class' => 'btn-primary']]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $remoteDesktop = RemoteDesktopFactory::createFromForm($form, $user);
            $em = $this->getDoctrine()->getManager();
            $em->persist($remoteDesktop);
            $em->flush();

            return $this->redirectToRoute('cloudinstances.new', ['remoteDesktop' => $remoteDesktop->getId()]);
        } else {
            return $this->render(
                'AppBundle:remoteDesktop:new.html.twig',
                [
                    'remoteDesktopKinds' => $availableRemoteDesktopKinds,
                    'form' => $form->createView()
                ]
            );
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
    public function scheduleForStopAtEndOfUsageHour(RemoteDesktop $remoteDesktop, int $usageHour)
    {
        $user = $this->getUser();

        if ($remoteDesktop->getUser()->getId() !== $user->getId()) {
            return $this->redirectToRoute('remotedesktops.index', [], Response::HTTP_FORBIDDEN);
        }

        $remoteDesktop
            ->getActiveCloudInstance()
            ->setScheduleForStopAt(DateTimeUtility::createDateTime()->add(new \DateInterval('PT' . $duration . 'S')));
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
