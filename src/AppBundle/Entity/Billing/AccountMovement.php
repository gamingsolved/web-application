<?php

namespace AppBundle\Entity\Billing;

use AppBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="account_movements")
 */
class AccountMovement
{
    const MOVEMENT_TYPE_DEBIT = 0;   // Abbuchung
    const MOVEMENT_TYPE_DEPOSIT = 1; // Einzahlung

    /**
     * @var string
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="users_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @var billableItem
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Billing\BillableItem")
     * @ORM\JoinColumn(name="billable_items_id", referencedColumnName="id")
     */
    protected $billableItem;

    /**
     * @var \DateTime $datetimeOccured
     *
     * @ORM\Column(name="datetime_occured", type="datetime", nullable=false)
     */
    protected $datetimeOccured;

    /**
     * @var int
     * @ORM\Column(name="movement_type", type="smallint", nullable=false)
     */
    protected $movementType;

    /**
     * @var float
     * @ORM\Column(name="amount", type="float", nullable=false)
     */
    protected $amount;

    protected function __construct() {}

    public static function createDebitMovement(User $user, BillableItem $billableItem, float $amount) : AccountMovement
    {
        if ($amount >= 0.0) {
            throw new \Exception('Debit amount must be negative, ' . $amount . ' is not.');
        }

        $accountMovement = new AccountMovement();

        $accountMovement->user = $user;
        $accountMovement->billableItem = $billableItem;
        $accountMovement->movementType = self::MOVEMENT_TYPE_DEBIT;
        $accountMovement->amount = $amount;

        return $accountMovement;
    }
}
