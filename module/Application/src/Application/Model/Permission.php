<?php

namespace Application\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Permission
 *
 * @ORM\Entity(repositoryClass="Application\Repository\PermissionRepository")
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="permission_unique", columns={"name"})})
 */
class Permission extends AbstractModel
{

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * Constructor
     * @param string $name
     */
    public function __construct($name = null)
    {
        $this->setName($name);
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Permission
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

}