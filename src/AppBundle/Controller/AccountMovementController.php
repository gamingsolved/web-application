<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Billing\AccountMovement;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class AccountMovementController extends Controller
{
    public function newDepositAction(Request $request)
    {
        /** @var Form $form */
        $form = $this
            ->createFormBuilder()
            ->add(
                'amount',
                ChoiceType::class,
                [
                    'choices' => [
                        '$5.00' => '5.0',
                        '$10.00' => '10.0'
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

        if ($form->isSubmitted() && $form->isValid()) {

            $user = $this->getUser();
            $accountMovement = AccountMovement::createDepositMovement($user, (float)$form->get('amount')->getData());
            $em = $this->getDoctrine()->getManager();
            $em->persist($accountMovement);
            $em->flush();

            return $this->redirectToRoute('payment.new', ['accountMovement' => $accountMovement->getId()]);

        } else {

            return $this->render(
                'AppBundle:accountMovement:newDeposit.html.twig',
                ['form' => $form->createView()]
            );

        }
    }
}
