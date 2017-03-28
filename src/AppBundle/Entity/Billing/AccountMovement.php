<?php

namespace AppBundle\Entity\Billing;

use AppBundle\Entity\User;
use AppBundle\Utility\DateTimeUtility;
use Doctrine\ORM\Mapping as ORM;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="account_movements")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\Billing\AccountMovementRepository")
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

    /**
     * @var PaymentInstruction
     * @ORM\OneToOne(targetEntity="JMS\Payment\CoreBundle\Entity\PaymentInstruction")
     */
    protected $paymentInstruction;

    /**
     * @var bool
     * @ORM\Column(name="payment_finished", type="boolean", nullable=true)
     */
    protected $paymentFinished;

    protected function __construct() {}

    public static function createDebitMovement(User $user, BillableItem $billableItem) : AccountMovement
    {
        if ($billableItem->getPrice() < 0.0) {
            throw new \Exception('Debit amount must not be negative, but ' . $billableItem->getPrice() . ' is.');
        }

        $accountMovement = new AccountMovement();

        $accountMovement->user = $user;
        $accountMovement->billableItem = $billableItem;
        $accountMovement->movementType = self::MOVEMENT_TYPE_DEBIT;
        $accountMovement->amount = (float)($billableItem->getPrice() * -1.0);
        $accountMovement->datetimeOccured = DateTimeUtility::createDateTime();

        return $accountMovement;
    }

    public static function createDepositMovement(User $user, float $amount) : AccountMovement
    {
        $accountMovement = new AccountMovement();

        $accountMovement->user = $user;
        $accountMovement->movementType = self::MOVEMENT_TYPE_DEBIT;
        $accountMovement->paymentFinished = false;
        $accountMovement->amount = (float)$amount;
        $accountMovement->datetimeOccured = DateTimeUtility::createDateTime();

        return $accountMovement;
    }

    public function getId() : string
    {
        return $this->id;
    }

    public function setPaymentInstruction(PaymentInstruction $paymentInstruction)
    {
        $this->paymentInstruction = $paymentInstruction;
    }

    public function getPaymentInstruction(): PaymentInstruction
    {
        return $this->paymentInstruction;
    }
}
