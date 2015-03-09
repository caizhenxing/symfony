<?php

namespace Park\CrudBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Friends
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Park\CrudBundle\Entity\FriendsRepository")
 */
class Friends
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
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;
	    /**
	    * @var \Doctrine\Common\Collections $persons
	    * @ORM\ManyToMany(targetEntity="Park\CrudBundle\Entity\Persons", mappedBy="persons")
	    */
    private  $friends;
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
     * @return Friends
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
     * 
     * @return string 
     */
    public function getFriends()
    {
        return $this->friends;
    }

    /**
     * 
     * @param $friends
     */
    public function setFriends($friends)
    {
        $this->friends = $friends;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->friends = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Add friends
     *
     * @param \Park\CrudBundle\Entity\Persons $friends
     * @return Friends
     */
    public function addFriend(\Park\CrudBundle\Entity\Persons $friends)
    {
        $this->friends[] = $friends;
    
        return $this;
    }

    /**
     * Remove friends
     *
     * @param \Park\CrudBundle\Entity\Persons $friends
     */
    public function removeFriend(\Park\CrudBundle\Entity\Persons $friends)
    {
        $this->friends->removeElement($friends);
    }
}