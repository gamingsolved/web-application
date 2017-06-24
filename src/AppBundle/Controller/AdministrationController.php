<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Billing\AccountMovement;
use AppBundle\Entity\Billing\AccountMovementRepository;
use AppBundle\Entity\User;
use AppBundle\Utility\DateTimeUtility;
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

        $currentAccountBalances = [];
        $anHourAgoAccountBalances = [];
        $twentyFourHoursAgoAccountBalances = [];

        /** @var User $user */
        foreach ($users as $user) {
            $currentAccountBalances[$user->getId()] = $accountMovementRepo->getAccountBalanceForUser($user);
            $anHourAgoAccountBalances[$user->getId()] = $accountMovementRepo->getAccountBalanceForUserUpUntil($user, DateTimeUtility::createDateTime()->modify('-1 hour'));
            $twentyFourHoursAgoAccountBalances[$user->getId()] = $accountMovementRepo->getAccountBalanceForUserUpUntil($user, DateTimeUtility::createDateTime()->modify('-24 hours'));
        }

        return $this->render(
            'AppBundle:administration:index.html.twig',
            [
                'users' => $users,
                'currentAccountBalances' => $currentAccountBalances,
                'anHourAgoAccountBalances' => $anHourAgoAccountBalances,
                'twentyFourHoursAgoAccountBalances' => $twentyFourHoursAgoAccountBalances
            ]
        );
    }

}
