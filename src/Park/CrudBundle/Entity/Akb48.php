<?php

namespace Park\CrudBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Park\CrudBundle\Entity\Persons;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * Akb48
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Park\CrudBundle\Entity\Akb48Repository")
 */
class Akb48
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     *  @ORM\OneToMany(targetEntity="Park\CrudBundle\Entity\Persons", mappedBy="akbfans")
     */
    private $name;
    public function __construct()
    {
    	$this->name = new ArrayCollection();
    }
    /**
     * @var string
     *
     * @ORM\Column(name="group", type="string", length=10)
     */
    protected $group;
    
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    
    /**
     * Set name
     *
     * @param string $name
     * @return Akb48
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }
    

    /**
     * Get group
     * @return  string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * 
     * @param string $group
     * @return Akb48
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * Add name
     *
     * @param \Park\CrudBundle\Entity\Persons $name
     * @return Akb48
     */
    public function addName(\Park\CrudBundle\Entity\Persons $name)
    {
        $this->name[] = $name;
    
        return $this;
    }

    /**
     * Remove name
     *
     * @param \Park\CrudBundle\Entity\Persons $name
     */
    public function removeName(\Park\CrudBundle\Entity\Persons $name)
    {
        $this->name->removeElement($name);
    }
}