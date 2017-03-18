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
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\RemoteDesktop", mappedBy="user")
     */
    private $remoteDesktops;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return ArrayCollection|RemoteDesktop[]
     */
    public function getRemoteDesktops()
    {
        return $this->remoteDesktops;
    }

    /**
     * @param ArrayCollection|RemoteDesktop[] $remoteDesktops
     */
    public function setRemoteDesktops($remoteDesktops)
    {
        $this->remoteDesktops = $remoteDesktops;
    }

}
