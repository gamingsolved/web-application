<?php

namespace AppBundle\Entity\Billing;

use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use Doctrine\ORM\EntityRepository;

class BillableItemRepository extends EntityRepository
{
    /**
     * @param \DateTime $dateTime
     * @return mixed Either false if none found, or BillableItem if found
     */
    public function findItemForDesktopCoveringDateTime(RemoteDesktop $remoteDesktop, \DateTime $dateTime)
    {

    }
}
