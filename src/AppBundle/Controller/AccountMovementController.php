<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Billing\AccountMovement;
use AppBundle\Entity\Billing\AccountMovementRepository;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEvent;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class AccountMovementController extends Controller
{
    protected function addEventAt(array &$events, \DateTime $at, string $description, float $moneyValue1, float $moneyValue2, string $stringValue)
    {
        $key = $at->format('Y-m-d H:i');
        if (array_key_exists($key, $events)) {
            $events[$key]['events'][] = [
                'description' => $description,
                'moneyValue1' => $moneyValue1,
                'moneyValue2' => $moneyValue2,
                'stringValue' => $stringValue
            ];
        } else {
            $events[$key]['occuredAt'] = $at;
            $events[$key]['events'][] = [
                'description' => $description,
                'moneyValue1' => $moneyValue1,
                'moneyValue2' => $moneyValue2,
                'stringValue' => $stringValue
            ];
        }
    }

    public function indexAction()
    {
        $user = $this->getUser();

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $events = [];

        /** @var AccountMovementRepository $accountMovementRepository */
        $accountMovementRepository = $em->getRepository(AccountMovement::class);
        $accountMovements = $accountMovementRepository->findBy(['user' => $user]);

        /** @var EntityRepository $remoteDesktopRepository */
        $remoteDesktopRepository = $em->getRepository(RemoteDesktop::class);
        $remoteDesktops = $remoteDesktopRepository->findBy(['user' => $user]);

        /** @var EntityRepository $remoteDesktopEventRepository */
        $remoteDesktopEventRepository = $em->getRepository(RemoteDesktopEvent::class);

        $remoteDesktopEvents = [];

        /** @var RemoteDesktop $remoteDesktop */
        foreach ($remoteDesktops as $remoteDesktop) {
            $thisRemoteDesktopEvents = $remoteDesktopEventRepository->findBy(['remoteDesktop' => $remoteDesktop]);
            /** @var RemoteDesktopEvent $remoteDesktopEvent */
            foreach ($thisRemoteDesktopEvents as $remoteDesktopEvent) {
                $remoteDesktopEvents[] = $remoteDesktopEvent;
            }
        }

        /** @var AccountMovement $accountMovement */
        foreach ($accountMovements as $accountMovement) {
            if ($accountMovement->getMovementType() === AccountMovement::MOVEMENT_TYPE_DEPOSIT) {
                $description = 'accountMovement.index.account_movement_deposit_description';
            } else {
                $description = 'accountMovement.index.account_movement_debit_description';
            }
            $this->addEventAt(
                $events,
                $accountMovement->getDatetimeOccured(),
                $description,
                abs($accountMovement->getAmount()),
                $accountMovementRepository->getAccountBalanceForUserUpUntil($user, $accountMovement->getDatetimeOccured()),
                ''
            );
        }

        /** @var RemoteDesktopEvent $remoteDesktopEvent */
        foreach ($remoteDesktopEvents as $remoteDesktopEvent) {
            if ($remoteDesktopEvent->getEventType() === RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER) {
                $description = 'accountMovement.index.remote_desktop_event_became_available';
            } else {
                $description = 'accountMovement.index.remote_desktop_event_became_unavailable';
            }
            $this->addEventAt($events, $remoteDesktopEvent->getDatetimeOccured(), $description, 0.0, 0.0, $remoteDesktopEvent->getRemoteDesktop()->getTitle());
        }

        krsort($events);

        return $this->render(
            'AppBundle:accountMovement:index.html.twig',
            [
                'events' => $events
            ]
        );
    }

    public function newDepositAction(Request $request)
    {
        $user = $this->getUser();

        /** @var Form $form */
        $form = $this
            ->createFormBuilder()
            ->add(
                'amount',
                ChoiceType::class,
                [
                    'choices' => [
                        '$5.00' => '5.0',
                        '$10.00' => '10.0',
                        '$25.00' => '25.0',
                        '$50.00' => '50.0'
                    ],
                    'expanded' => true,
                    'multiple' => false,
                    'label' => 'accountMovement.newDeposit.form.amount_label'
                ]
            )
            ->add(
                'send',
                SubmitType::class,
                ['label' => 'accountMovement.newDeposit.form.submit_label', 'attr' => ['class' => 'btn-primary']]
            )
            ->getForm();

        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();

        if ($form->isSubmitted() && $form->isValid()) {

            $user = $this->getUser();
            $accountMovement = AccountMovement::createDepositMovement($user, (float)$form->get('amount')->getData());
            $em->persist($accountMovement);
            $em->flush();

            return $this->redirectToRoute('payment.new', ['accountMovement' => $accountMovement->getId()]);

        } else {

            /** @var AccountMovementRepository $accountMovementRepository */
            $accountMovementRepository = $em->getRepository(AccountMovement::class);

            return $this->render(
                'AppBundle:accountMovement:newDeposit.html.twig',
                [
                    'form' => $form->createView(),
                    'balance' => $accountMovementRepository->getAccountBalanceForUser($user)
                ]
            );

        }
    }
}
