<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Billing\AccountMovement;
use AppBundle\Entity\Billing\AccountMovementRepository;
use AppBundle\Entity\User;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\PluginController\PluginControllerInterface;
use JMS\Payment\CoreBundle\PluginController\Result;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\Payment\CoreBundle\Form\ChoosePaymentMethodType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentController extends Controller
{

    protected function createPayment(AccountMovement $accountMovement)
    {
        $instruction = $accountMovement->getPaymentInstruction();
        $pendingTransaction = $instruction->getPendingTransaction();

        if ($pendingTransaction !== null) {
            return $pendingTransaction->getPayment();
        }

        $ppc = $this->get('payment.plugin_controller');
        $amount = $instruction->getAmount() - $instruction->getDepositedAmount();

        return $ppc->createPayment($instruction->getId(), $amount);
    }

    /**
     * @ParamConverter("accountMovement", class="AppBundle:Billing\AccountMovement")
     */
    public function finishAction(Request $request, AccountMovement $accountMovement)
    {
        /** @var User $user */
        $user = $this->getUser();

        if (   ($request->query->get('accountMovementIdHash') !== sha1($accountMovement->getId() . $this->getParameter('secret')))
            || ($accountMovement->getUser()->getId() !== $user->getId())) {
            return $this->render(
                'AppBundle:payment:finish.html.twig',
                [
                    'success' => false,
                    'accessDenied' => true,
                    'amount' => null,
                    'balance' => null
                ]
            );
        }

        $payment = $this->createPayment($accountMovement);

        /** @var PluginControllerInterface $ppc */
        $ppc = $this->get('payment.plugin_controller');
        $result = $ppc->approveAndDeposit($payment->getId(), $payment->getTargetAmount());

        if ($result->getStatus() === Result::STATUS_SUCCESS) {

            $em = $this->getDoctrine()->getManager();
            /** @var AccountMovementRepository $accountMovementRepository */
            $accountMovementRepository = $em->getRepository(AccountMovement::class);

            $accountMovement->setPaymentFinished(true);
            $em->persist($accountMovement);
            $em->flush();

            return $this->render(
                'AppBundle:payment:finish.html.twig',
                [
                    'success' => true,
                    'accessDenied' => false,
                    'amount' => $accountMovement->getAmount(),
                    'balance' => $accountMovementRepository->getAccountBalanceForUser($user)
                ]
            );
        }

        if ($result->getStatus() === Result::STATUS_PENDING) {
            $ex = $result->getPluginException();

            if ($ex instanceof ActionRequiredException) {
                $action = $ex->getAction();

                if ($action instanceof VisitUrl) {
                    return $this->redirect($action->getUrl());
                }
            }
        }

        // Something went wrong
        throw $result->getPluginException();
    }

    /**
     * @ParamConverter("accountMovement", class="AppBundle:Billing\AccountMovement")
     */
    public function cancelAction(Request $request, AccountMovement $accountMovement)
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($accountMovement->getUser()->getId() !== $user->getId()) {
            return $this->render(
                'AppBundle:payment:finish.html.twig',
                [
                    'success' => false,
                    'accessDenied' => true,
                    'amount' => null,
                    'balance' => null
                ]
            );
        }

        $em = $this->getDoctrine()->getManager();
        /** @var AccountMovementRepository $accountMovementRepository */
        $accountMovementRepository = $em->getRepository(AccountMovement::class);

        return $this->render(
            'AppBundle:payment:finish.html.twig',
            [
                'success' => false,
                'accessDenied' => false,
                'amount' => $accountMovement->getAmount(),
                'balance' => $accountMovementRepository->getAccountBalanceForUser($user)
            ]
        );
    }

    /**
     * @ParamConverter("accountMovement", class="AppBundle:Billing\AccountMovement")
     */
    public function newAction(Request $request, AccountMovement $accountMovement)
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($accountMovement->getUser()->getId() !== $user->getId()) {
            return $this->render(
                'AppBundle:payment:new.html.twig',
                [
                    'accessDenied' => true,
                    'amount' => null,
                    'form' => null
                ]
            );
        }

        $predefined_data = [
            'paypal_express_checkout' => [
                'return_url' =>
                    $this->generateUrl(
                        'payment.finish',
                        ['accountMovement' => $accountMovement->getId()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ) . '?accountMovementIdHash=' . sha1($accountMovement->getId() . $this->getParameter('secret')),
                'cancel_url' =>
                    $this->generateUrl(
                        'payment.cancel',
                        ['accountMovement' => $accountMovement->getId()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                'useraction' =>
                    'commit',
            ],
        ];

        $form = $this->createForm(ChoosePaymentMethodType::class, null, [
            'amount'          => $accountMovement->getAmount(),
            'currency'        => 'USD',
            'default_method'  => 'paypal_express_checkout',
            'predefined_data' => $predefined_data
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var PluginControllerInterface $ppc */
            $ppc = $this->get('payment.plugin_controller');
            $ppc->createPaymentInstruction($instruction = $form->getData());

            $accountMovement->setPaymentInstruction($instruction);
            $em = $this->getDoctrine()->getManager();
            $em->persist($accountMovement);
            $em->flush();

            $payment = $this->createPayment($accountMovement);

            $ppc = $this->get('payment.plugin_controller');
            /** @var Result $result */
            $result = $ppc->approveAndDeposit($payment->getId(), $payment->getTargetAmount());

            if ($result->getStatus() === Result::STATUS_PENDING) {
                $ex = $result->getPluginException();

                if ($ex instanceof ActionRequiredException) {
                    $action = $ex->getAction();

                    if ($action instanceof VisitUrl) {
                        return $this->redirect($action->getUrl());
                    }
                }
            }

            // Something went wrong
            throw $result->getPluginException();
        }

        return $this->render(
            'AppBundle:payment:new.html.twig',
            [
                'accessDenied' => false,
                'amount' => $accountMovement->getAmount(),
                'form' => $form->createView()
            ]
        );
    }

}
