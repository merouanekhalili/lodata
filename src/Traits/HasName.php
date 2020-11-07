<?php

namespace Flat3\Lodata\Traits;

use Flat3\Lodata\Helper\Name;

trait HasName
{
    /** @var Name $name Resource identifier */
    protected $name;

    /**
     * Get the name of this nominal item
     * @return string Name
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function __toString()
    {
        return (string) $this->name;
    }

    public function setName($name)
    {
        $this->name = $name instanceof Name ? $name : new Name($name);

        return $this;
    }
}
