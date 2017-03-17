<?php

namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as FOSUser;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User extends FOSUser
{
    /**
     * @var string
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var ArrayCollection|RemoteDesktop[]
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\RemoteDesktop", mappedBy="users")
     */
    private $desktops;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return ArrayCollection|RemoteDesktop[]
     */
    public function getDesktops()
    {
        return $this->desktops;
    }

    /**
     * @param ArrayCollection|RemoteDesktop[] $desktops
     */
    public function setDesktops($desktops)
    {
        $this->desktops = $desktops;
    }

}
