<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Billing\AccountMovement;
use AppBundle\Entity\Billing\AccountMovementRepository;
use AppBundle\Entity\Billing\BillableItem;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopRelevantForBillingEvent;
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
    protected function addEventAt(
        array &$eventblocks,
        \DateTime $at,
        string $description,
        float $moneyValue = 0.0,
        string $stringValue = '',
        int $billableItemType = null,
        RemoteDesktop $remoteDesktop = null)
    {
        $billingInterval = null;
        $remoteDesktopTitle = '';
        if (!is_null($remoteDesktop)) {
            $remoteDesktopTitle = $remoteDesktop->getTitle();
            if ($billableItemType === BillableItem::TYPE_USAGE) {
                $billingInterval = $remoteDesktop->getUsageCostsIntervalAsString();
            } elseif ($billableItemType == BillableItem::TYPE_PROVISIONING) {
                $billingInterval = $remoteDesktop->getProvisioningCostsIntervalAsString();
            }
        }

        $key = $at->format('Y-m-d H:i');
        if (array_key_exists($key, $eventblocks)) {
            $eventblocks[$key]['events'][] = [
                'description' => $description,
                'moneyValue'  => $moneyValue,
                'stringValue' => $stringValue,
                'billableItemType' => $billableItemType,
                'remoteDesktopTitle' => $remoteDesktopTitle,
                'billingInterval' => $billingInterval
            ];
        } else {
            $eventblocks[$key]['occuredAt'] = $at;
            $eventblocks[$key]['events'][] = [
                'description' => $description,
                'moneyValue'  => $moneyValue,
                'stringValue' => $stringValue,
                'billableItemType' => $billableItemType,
                'remoteDesktopTitle' => $remoteDesktopTitle,
                'billingInterval' => $billingInterval
            ];
        }
    }

    public function indexAction()
    {
        $user = $this->getUser();

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $eventblocks = [];

        /** @var AccountMovementRepository $accountMovementRepository */
        $accountMovementRepository = $em->getRepository(AccountMovement::class);
        $accountMovements = $accountMovementRepository->findBy(['user' => $user]);

        /** @var EntityRepository $remoteDesktopRepository */
        $remoteDesktopRepository = $em->getRepository(RemoteDesktop::class);
        $remoteDesktops = $remoteDesktopRepository->findBy(['user' => $user]);

        /** @var EntityRepository $remoteDesktopRelevantForBillingEventRepository */
        $remoteDesktopRelevantForBillingEventRepository = $em->getRepository(RemoteDesktopRelevantForBillingEvent::class);

        $remoteDesktopRelevantForBillingEvents = [];

        /** @var RemoteDesktop $remoteDesktop */
        foreach ($remoteDesktops as $remoteDesktop) {
            $thisRemoteDesktopRelevantForBillingEvents = $remoteDesktopRelevantForBillingEventRepository->findBy(['remoteDesktop' => $remoteDesktop]);
            /** @var RemoteDesktopRelevantForBillingEvent $remoteDesktopRelevantForBillingEvent */
            foreach ($thisRemoteDesktopRelevantForBillingEvents as $remoteDesktopRelevantForBillingEvent) {
                $remoteDesktopRelevantForBillingEvents[] = $remoteDesktopRelevantForBillingEvent;
            }
        }

        /** @var AccountMovement $accountMovement */
        foreach ($accountMovements as $accountMovement) {
            if (    $accountMovement->getMovementType() === AccountMovement::MOVEMENT_TYPE_DEBIT
                || ($accountMovement->getMovementType() === AccountMovement::MOVEMENT_TYPE_DEPOSIT && $accountMovement->getPaymentFinished()) ) {

                if ($accountMovement->getMovementType() === AccountMovement::MOVEMENT_TYPE_DEPOSIT) {
                    $this->addEventAt(
                        $eventblocks,
                        $accountMovement->getDatetimeOccured(),
                        'accountMovement.index.account_movement_deposit_description',
                        abs($accountMovement->getAmount())
                    );
                } else {
                    $this->addEventAt(
                        $eventblocks,
                        $accountMovement->getDatetimeOccured(),
                        'accountMovement.index.account_movement_debit_description',
                        abs($accountMovement->getAmount()),
                        '',
                        $accountMovement->getBillableItem()->getType(),
                        $accountMovement->getBillableItem()->getRemoteDesktop()
                    );
                }

            }
        }

        foreach ($eventblocks as $index => $eventblock) {
            /** @var \DateTime $occuredAt */
            $occuredAt = clone($eventblock['occuredAt']);
            $occuredAt->add(new \DateInterval('PT59S'));
            $eventblocks[$index]['accountBalance'] =
                $accountMovementRepository->getAccountBalanceForUserUpUntil($user, $occuredAt);
        }

        /** @var RemoteDesktopRelevantForBillingEvent $remoteDesktopRelevantForBillingEvent */
        foreach ($remoteDesktopRelevantForBillingEvents as $remoteDesktopRelevantForBillingEvent) {
            if ($remoteDesktopRelevantForBillingEvent->getEventType() === RemoteDesktopRelevantForBillingEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER) {
                $description = 'accountMovement.index.remote_desktop_event_became_available';
            } elseif ($remoteDesktopRelevantForBillingEvent->getEventType() === RemoteDesktopRelevantForBillingEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER) {
                $description = 'accountMovement.index.remote_desktop_event_became_unavailable';
            } elseif ($remoteDesktopRelevantForBillingEvent->getEventType() === RemoteDesktopRelevantForBillingEvent::EVENT_TYPE_DESKTOP_WAS_PROVISIONED_FOR_USER) {
                $description = 'accountMovement.index.remote_desktop_event_was_provisioned';
            } elseif ($remoteDesktopRelevantForBillingEvent->getEventType() === RemoteDesktopRelevantForBillingEvent::EVENT_TYPE_DESKTOP_WAS_UNPROVISIONED_FOR_USER) {
                $description = 'accountMovement.index.remote_desktop_event_was_unprovisioned';
            } else {
                throw new \Exception('Unknown remote desktop event type ' . $remoteDesktopRelevantForBillingEvent->getEventType());
            }
            $this->addEventAt(
                $eventblocks,
                $remoteDesktopRelevantForBillingEvent->getDatetimeOccured(),
                $description,
                0.0,
                $remoteDesktopRelevantForBillingEvent->getRemoteDesktop()->getTitle()
            );
        }

        krsort($eventblocks);

        return $this->render(
            'AppBundle:accountMovement:index.html.twig',
            [
                'eventblocks' => $eventblocks
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
