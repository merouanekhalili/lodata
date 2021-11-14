<?php

declare(strict_types=1);

namespace Flat3\Lodata\Operation;

class EloquentFunction extends Function_
{
    protected $method;

    public function setMethod($method): self
    {
        $this->method = $method;
        return $this;
    }

    public function invoke(array $arguments = [])
    {
        $entity = $this->getBoundParameter();
        $instance = $entity->getSource();

        return call_user_func_array([$instance, $this->method], array_values($arguments));
    }
}