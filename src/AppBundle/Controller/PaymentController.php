<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Billing\AccountMovement;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\PluginController\PluginControllerInterface;
use JMS\Payment\CoreBundle\PluginController\Result;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\Payment\CoreBundle\Form\ChoosePaymentMethodType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class PaymentController extends Controller
{

    private function createPayment(PaymentInstruction $instruction)
    {
        $pendingTransaction = $instruction->getPendingTransaction();

        if ($pendingTransaction !== null) {
            return $pendingTransaction->getPayment();
        }

        $ppc = $this->get('payment.plugin_controller');
        $amount = $instruction->getAmount() - $instruction->getDepositedAmount();

        return $ppc->createPayment($instruction->getId(), $amount);
    }

    /**
     * @ParamConverter("remoteDesktop", class="AppBundle:RemoteDesktop\RemoteDesktop")
     */
    public function successAction(AccountMovement $accountMovement)
    {

    }

    /**
     * @ParamConverter("remoteDesktop", class="AppBundle:RemoteDesktop\RemoteDesktop")
     */
    public function newAction(Request $request, AccountMovement $accountMovement)
    {
        $predefined_data = [
            'paypal_express_checkout' => [
                'return_url'=>
                    'http://localhost:8000/en/'
                    . $accountMovement->getId()
                    . 'payment/finish?hashedAccountMovementId='
                    . sha1($accountMovement->getId() . $this->container->getParameter('secret')),
                'cancel_url' =>
                    'http://localhost:8000/en/'. $accountMovement->getId() .'payment/cancel',
                'useraction' =>
                    'commit',
            ],
        ];

        $form = $this->createForm(ChoosePaymentMethodType::class, null, [
            'amount'          => $accountMovement->getAmount(),
            'currency'        => 'USD',
            'predefined_data' => $predefined_data
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var PluginControllerInterface $ppc */
            $ppc = $this->get('payment.plugin_controller');
            $ppc->createPaymentInstruction($instruction = $form->getData());

            $payment = $this->createPayment($instruction);

            $ppc = $this->get('payment.plugin_controller');
            /** @var Result $result */
            $result = $ppc->approveAndDeposit($payment->getId(), $payment->getTargetAmount());

            if ($result->getStatus() === Result::STATUS_SUCCESS) {
                return $this->render(
                    'AppBundle:payment:success.html.twig'
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

        return $this->render(
            'AppBundle:payment:new.html.twig',
            [
                'form' => $form->createView(),
                'amount' => 10.0
            ]
        );
    }

}
