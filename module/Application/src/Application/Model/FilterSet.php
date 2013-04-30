<?php

namespace Application\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Filter
 *
 * @ORM\Entity(repositoryClass="Application\Repository\FilterSetRepository")
 */
class FilterSet extends AbstractModel
{

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=false)
     */
    private $name;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Filter")
     * @ORM\OrderBy({"name" = "ASC"})
     */
    private $filters;

    /**
     * Constructor
     * @param string $name
     */
    public function __construct($name = null)
    {
        $this->filters = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setName($name);
    }

    /**
     * Set name
     *
     * @param string $name
     * @return FilterSet
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
     * Get filters
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Add a filter
     * @param Filter $filter
     * @return FilterSet
     */
    public function addFilter(Filter $filter)
    {
        if (!$this->getFilters()->contains($filter)) {
            $this->getFilters()->add($filter);
        }

        return $this;
    }

}
