<?php

declare(strict_types=1);

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\Interfaces\NameInterface;
use Flat3\Lodata\Interfaces\Operation\ArgumentInterface;
use Flat3\Lodata\Traits\HasName;
use ReflectionParameter;

/**
 * Argument
 * @package Flat3\Lodata\Operation
 */
abstract class Argument implements NameInterface
{
    use HasName;

    /**
     * The reflection parameter on the operations invocation method
     * @var ReflectionParameter $parameter
     */
    protected $parameter;

    public function __construct(ReflectionParameter $parameter)
    {
        $this->parameter = $parameter;
        $this->setName($parameter->getName());
    }

    /**
     * Whether this argument can be null
     * @return bool
     */
    public function isNullable(): bool
    {
        return false;
    }

    /**
     * Generate an instance of this argument with the value of the provided source
     * @param  mixed|null  $source
     * @return ArgumentInterface
     */
    abstract public function generate($source = null): ArgumentInterface;

    abstract public function assertValidParameter($parameter): void;
}
