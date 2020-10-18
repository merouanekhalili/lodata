<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Interfaces\DeclaredPropertyInterface;
use Flat3\Lodata\Interfaces\DynamicPropertyInterface;
use Flat3\Lodata\Interfaces\NameInterface;
use Flat3\Lodata\Interfaces\TypeInterface;
use Flat3\Lodata\Traits\HasName;
use Flat3\Lodata\Traits\HasType;

abstract class Property implements TypeInterface, NameInterface
{
    use HasName;
    use HasType;

    /** @var bool $nullable Whether this property is nullable */
    protected $nullable = true;

    /** @var bool $searchable Whether this property is included as part of $search requests */
    protected $searchable = false;

    /** @var bool $filterable Whether this property can be used in a $filter expression */
    protected $filterable = true;

    /** @var bool $alternativeKey Whether this property can be used as an alternative key */
    protected $alternativeKey = false;

    public function __construct($name, TypeInterface $type)
    {
        $this->setName($name);
        $this->type = $type;

        if (
            !$this instanceof DeclaredPropertyInterface &&
            !$this instanceof DynamicPropertyInterface &&
            !$this instanceof NavigationProperty
        ) {
            throw new InternalServerErrorException(
                sprintf('An property must implement either %s or %s', DeclaredPropertyInterface::class,
                    DynamicPropertyInterface::class)
            );
        }
    }

    public static function factory($name, TypeInterface $type): self
    {
        return new class($name, $type) extends Property implements DeclaredPropertyInterface {
        };
    }

    /**
     * Get whether this property is included in search
     *
     * @return bool
     */
    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    /**
     * Set whether this property is included in search
     *
     * @param  bool  $searchable
     *
     * @return $this
     */
    public function setSearchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * @return bool
     */
    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    /**
     * @param  bool  $filterable
     *
     * @return Property
     */
    public function setFilterable(bool $filterable): Property
    {
        $this->filterable = $filterable;

        return $this;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Set whether this property can be made null
     *
     * @param  bool  $nullable
     *
     * @return $this
     */
    public function setNullable(bool $nullable): self
    {
        $this->nullable = $nullable;

        return $this;
    }

    public function isAlternativeKey(): bool
    {
        return $this->alternativeKey;
    }

    public function setAlternativeKey(bool $alternativeKey = true): self
    {
        $this->alternativeKey = $alternativeKey;

        return $this;
    }
}