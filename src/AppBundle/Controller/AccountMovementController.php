<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Billing\AccountMovement;
use AppBundle\Entity\Billing\AccountMovementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class AccountMovementController extends Controller
{
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
