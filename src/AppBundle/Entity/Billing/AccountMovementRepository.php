<?php

namespace AppBundle\Entity\Billing;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

class AccountMovementRepository extends EntityRepository
{
    public function getAccountBalanceForUser(User $user) : float
    {
        $qb = $this->createQueryBuilder('a');

        $query = $qb
            ->add('select', 'SUM(a.amount)')
            ->where('a.user = :user')
            ->setParameter(':user', $user)
            ->getQuery();

        $res = $query->getOneOrNullResult();
        if (is_null($res)) {
            return 0.0;
        } else {
            return (float)$res[1];
        }
    }
}
