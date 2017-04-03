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
            ->andWhere('a.paymentFinished = true OR a.paymentFinished IS NULL')
            ->setParameter(':user', $user)
            ->getQuery();

        $res = $query->getOneOrNullResult();
        if (is_null($res)) {
            return 0.0;
        } else {
            return (float)$res[1];
        }
    }

    public function getAccountBalanceForUserUpUntil(User $user, \DateTime $upUntil) : float
    {
        $qb = $this->createQueryBuilder('a');

        $query = $qb
            ->add('select', 'SUM(a.amount)')
            ->where('a.user = :user')
            ->andWhere('a.paymentFinished = true OR a.paymentFinished IS NULL')
            ->andWhere('a.datetimeOccured <= :upUntil')
            ->setParameter(':user', $user)
            ->setParameter(':upUntil', $upUntil)
            ->getQuery();

        $res = $query->getOneOrNullResult();
        if (is_null($res)) {
            return 0.0;
        } else {
            return (float)$res[1];
        }
    }
}
