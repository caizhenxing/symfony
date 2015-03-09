<?php
namespace Park\ThroughBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="Groups")
 */
class Group {
    
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\OneToMany(targetEntity="Park\ThroughBundle\Entity\User", mappedBy="group")
     */
    protected $members;

    /**
     * Creates a Doctrine Collection for members.
     */
    public function __construct()
    {
        $this->members = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Override __toString() method to return the name of the group
     * @return mixed
     */
    public function __toString()
    {
       return $this->name;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setMembers($members)
    {
        $this->members = $members;
    }

    public function getMembers()
    {
        return $this->members;
    }
}
