<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Billing\AccountMovement;
use AppBundle\Entity\Billing\AccountMovementRepository;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopRelevantForBillingEvent;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Entity\RemoteDesktop\RemoteDesktopKind;
use AppBundle\Entity\User;
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
            if ($remoteDesktop->getStatus() === RemoteDesktop::STATUS_REBOOTING) {
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
                        $em->getRepository(RemoteDesktopRelevantForBillingEvent::class)
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

        $availableRemoteDesktopKinds = RemoteDesktopKind::getAvailableKinds($user);
        /** @var RemoteDesktopKind $remoteDesktopKind */
        foreach ($availableRemoteDesktopKinds as $remoteDesktopKind) {
            $choices[] = $remoteDesktopKind->getIdentifier();
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

        if ($remoteDesktop->getUsageCostsForOneInterval() > $accountMovementRepository->getAccountBalanceForUser($user)) {
            return $this->render(
                'AppBundle:remoteDesktop:insufficientAccountBalance.html.twig',
                [
                    'usageCostsForOneInterval' => $remoteDesktop->getUsageCostsForOneInterval(),
                    'usageCostsIntervalAsString' => $remoteDesktop->getUsageCostsIntervalAsString(),
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
        /** @var User $user */
        $user = $this->getUser();

        if (!($remoteDesktop->getUser()->getId() === $user->getId() || $user->hasRole('ROLE_ADMIN'))) {
            return $this->redirectToRoute('remotedesktops.index', [], Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();

        if ($remoteDesktop->getStatus() === RemoteDesktop::STATUS_NEVER_LAUNCHED) {
            $em->remove($remoteDesktop);
        } else {
            $remoteDesktop->scheduleForTermination();
            $em->persist($remoteDesktop);
        }

        $em->flush();

        if ($user->hasRole('ROLE_ADMIN')) {
            return $this->redirect($request->headers->get('referer'));
        } else {
            return $this->redirectToRoute('remotedesktops.index');
        }
    }

    /**
     * @ParamConverter("remoteDesktop", class="AppBundle:RemoteDesktop\RemoteDesktop")
     */
    public function rebootAction(RemoteDesktop $remoteDesktop, Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!($remoteDesktop->getUser()->getId() === $user->getId() || $user->hasRole('ROLE_ADMIN'))) {
            return $this->redirectToRoute('remotedesktops.index', [], Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();

        $remoteDesktop->scheduleForReboot();
        $em->persist($remoteDesktop);
        $em->flush();

        if ($user->hasRole('ROLE_ADMIN')) {
            return $this->redirect($request->headers->get('referer'));
        } else {
            return $this->redirectToRoute('remotedesktops.index');
        }
    }

    /**
     * @ParamConverter("remoteDesktop", class="AppBundle:RemoteDesktop\RemoteDesktop")
     */
    public function scheduleForStopAtEndOfUsageHourAction(RemoteDesktop $remoteDesktop, int $usageHour)
    {
        if ($usageHour < 0 || $usageHour > 7 || $remoteDesktop->getStatus() !== RemoteDesktop::STATUS_READY_TO_USE) {
            $this->createAccessDeniedException();
        }

        $user = $this->getUser();

        if ($remoteDesktop->getUser()->getId() !== $user->getId()) {
            $this->createAccessDeniedException();
        }

        $em = $this->getDoctrine()->getManager();

        $rdas = new RemoteDesktopAutostopService();
        $optimalHourlyAutostopTimes = $rdas->getOptimalHourlyAutostopTimesForRemoteDesktop(
            $remoteDesktop,
            $em->getRepository(RemoteDesktopRelevantForBillingEvent::class)
        );

        if (!is_array($optimalHourlyAutostopTimes) || sizeof($optimalHourlyAutostopTimes) !== 8) {
            throw new \Exception(
                'Expected 8 optimalHourlyAutostopTimes, got something else: ' .
                print_r($optimalHourlyAutostopTimes, true)
            );
        }

        $remoteDesktop->setScheduleForStopAt($optimalHourlyAutostopTimes[$usageHour]);
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

        $resolutionsAndBitrates = [
            ['width' => '1024', 'height' => '768',  'bitrate' => '10'],
            ['width' => '1280', 'height' => '720',  'bitrate' => '12'],
            ['width' => '1280', 'height' => '800',  'bitrate' => '15'],
            ['width' => '1536', 'height' => '1152', 'bitrate' => '17'],
            ['width' => '1920', 'height' => '1080', 'bitrate' => '23'],
            ['width' => '1920', 'height' => '1200', 'bitrate' => '25'],
            ['width' => '2048', 'height' => '1536', 'bitrate' => '28'],
            ['width' => '2560', 'height' => '1440', 'bitrate' => '30']
        ];

        $bitrate = null;
        foreach ($resolutionsAndBitrates as $resolutionsAndBitrate) {
            if ($resolutionsAndBitrate['width'] === $width && $resolutionsAndBitrate['height'] === $height) {
                $bitrate = $resolutionsAndBitrate['bitrate'];
            }
        }
        if (is_null($bitrate)) {
            throw new \Exception('Could not match width ' . $width . ' and height ' . $height . ' to a bitrate.');
        }

        $response = $this->render(
            'AppBundle:remoteDesktop:sgxFile/tag.sgx.twig',
            [
                'ip'       => $remoteDesktop->getPublicAddress(),
                'width'    => $width,
                'height'   => $height,
                'bitrate'  => $bitrate,
                'key'      => $remoteDesktop->getId(),
                'password' => $remoteDesktop->getAdminPassword(),

                // Games only work with mouse mode "relative"
                'mouseRelative' => $remoteDesktop->getKind()->getMouseRelativeValue()
            ]
        );

        $response->headers->set('Content-Type', 'text/plain');

        return $response;
    }
}
