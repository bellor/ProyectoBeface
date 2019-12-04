<?php
namespace AppBundle\Entity;

/**
 * Follow
 */
class Follow
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \AppBundle\Entity\User
     */
    private $user;

    /**
     * @var \AppBundle\Entity\User
     */
    private $followed;


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
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return Follow
     */
    public function setUser(\AppBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \AppBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set followed
     *
     * @param \AppBundle\Entity\User $followed
     *
     * @return Follow
     */
    public function setFollowed(\AppBundle\Entity\User $followed = null)
    {
        $this->followed = $followed;

        return $this;
    }

    /**
     * Get followed
     *
     * @return \AppBundle\Entity\User
     */
    public function getFollowed()
    {
        return $this->followed;
    }
}

