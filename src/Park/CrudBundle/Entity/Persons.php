<?php

namespace Park\CrudBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Park\CrudBundle\Entity\Akb48;

/**
 * Persons
 *
 * @ORM\Table(name="Person")
 * @ORM\Entity(repositoryClass="Park\CrudBundle\Entity\PersonsRepository")
 * @ORM\HasLifecycleCallbacks() 
 */
class Persons
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
     * @ORM\Column(name="name", type="string", length=50)
     **/
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="job", type="string", length=50)
     */
    
    private $job;

    /**
     * @ORM\ManyToOne(targetEntity="Park\CrudBundle\Entity\Akb48", inversedBy="name")
     * @ORM\JoinColumn(name="akb48_id", referencedColumnName="id")
     */
    protected $akbfans;
    /**
     * created Time/Date
     *
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected $createdAt;
    
    /**
     * updated Time/Date
     *
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     */
    protected $updatedAt;
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
     * @return Persons
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
     * Set job
     *
     * @param string $job
     * @return Persons
     */
    public function setJob($job)
    {
        $this->job = $job;
    
        return $this;
    }

    /**
     * Get job
     *
     * @return string 
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * Set akbfans
     *
     * @param \Park\CrudBundle\Entity\akb48 $akbfans
     * @return Persons
     */
    public function setAkbfans(\Park\CrudBundle\Entity\akb48 $akbfans = null)
    {
        $this->akbfans = $akbfans;
    
        return $this;
    }

    /**
     * Get akbfans
     *
     * @return \Park\CrudBundle\Entity\akb48 
     */
    public function getAkbfans()
    {
        return $this->akbfans;
    }


    /**
     * 
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * 
     * @ORM\PrePersist 
     */
    public function setCreatedAt()
    {
        $this->createdAt = new \DateTime();  
        $this->updatedAt = new \DateTime(); 
    }

    /**
     * 
     *@return \DateTime 
     */
    public function getUpdatedAt()
    {
        $this->updatedAt = new \DateTime();  
    }

    /**
     * 
     * @ORM\PreUpdate
     */
    public function setUpdatedAt()
    {
        $this->updatedAt = $updatedAt;
    }
}