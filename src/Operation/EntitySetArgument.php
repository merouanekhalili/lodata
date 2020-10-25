<?php

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\Operation\ArgumentInterface;

class EntitySetArgument extends Argument
{
    public function generate($source = null): ArgumentInterface
    {
        if (!$source instanceof Transaction) {
            throw new InternalServerErrorException(
                'invalid_transaction',
                'The source of an entity set is expected to be a transaction'
            );
        }

        $entitySet = Lodata::getEntitySet($this->getName());

        if (!$entitySet instanceof EntitySet) {
            throw new InternalServerErrorException(
                'invalid_entity_set',
                'Could not find entity set: '.$this->getName()
            );
        }

        $entitySet = clone $entitySet;
        $entitySet->setTransaction($source);
        return $entitySet;
    }

    public function getType(): EntityType
    {
        $reflectedSet = $this->parameter->getName();
        return Lodata::getEntitySet($reflectedSet)->getType();
    }
}
