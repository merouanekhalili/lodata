<?php

declare(strict_types=1);

namespace Flat3\Lodata\Attributes;

use Attribute;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Type;
use Illuminate\Database\Eloquent\Model;

#[Attribute]
abstract class LodataOperation
{
    const operationClass = null;

    protected ?string $name = null;
    protected ?string $bind = null;
    protected ?Type $return = null;

    public function __construct(?string $name = null, ?string $bind = null, ?string $return = null)
    {
        $this->name = $name;
        $this->bind = $bind;
        if ($return) {
            $this->return = Lodata::getEntityType($return);
        }
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getBindingParameterName(): ?string
    {
        return $this->bind;
    }

    public function getReturnType(): ?Type
    {
        return $this->return;
    }

    public function toOperation(string $object, ?string $method = null): Operation
    {
        if (is_a($object, Model::class, true)) {
            $func = new Operation\EloquentFunction($this->name ?: $method);
            $func->setMethod($method);
        } else {
            $func = new ($this::operationClass)($this->name ?: $method);
        }

        $func->setCallable([$object, $method]);

        if ($this->bind) {
            $func->setBindingParameterName($this->bind);
        }

        if ($this->return) {
            $func->setReturnType($this->return);
        }

        return $func;
    }
}