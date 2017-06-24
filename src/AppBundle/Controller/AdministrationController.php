<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Billing\AccountMovement;
use AppBundle\Entity\Billing\AccountMovementRepository;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AdministrationController extends Controller
{
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        /** @var EntityRepository $userRepo */
        $userRepo = $em->getRepository(User::class);
        $users = $userRepo->findAll();

        /** @var AccountMovementRepository $accountMovementRepo */
        $accountMovementRepo = $em->getRepository(AccountMovement::class);

        $accountBalances = [];

        /** @var User $user */
        foreach ($users as $user) {
            $accountBalances[$user->getId()] = $accountMovementRepo->getAccountBalanceForUser($user);
        }

        return $this->render(
            'AppBundle:administration:index.html.twig',
            [
                'users' => $users,
                'accountBalances' => $accountBalances
            ]
        );
    }

}
