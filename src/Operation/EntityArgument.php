<?php

declare(strict_types=1);

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\Entity;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\Operation\ArgumentInterface;

/**
 * Entity Argument
 * @package Flat3\Lodata\Operation
 */
class EntityArgument extends Argument
{
    protected $type;

    public function setType(EntityType $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Generate an Entity argument
     * @param  null  $source
     * @return ArgumentInterface
     */
    public function generate($source = null): ArgumentInterface
    {
        if ($source) {
            return $source;
        }

        $entityType = $this->type ?: Lodata::getEntityType($this->getName());

        if (!$entityType) {
            throw new InternalServerErrorException('invalid_entity_type', 'Entity of this type could not be generated');
        }

        $entity = new Entity();
        $entity->setType($entityType);

        return $entity;
    }

    /**
     * Get the entity type
     *
     * @return EntityType
     */
    public function getType(): EntityType
    {
        if ($this->type) {
            return $this->type;
        }

        $reflectedType = $this->parameter->getName();
        return Lodata::getEntityType($reflectedType);
    }

    public function assertValidParameter($parameter): void
    {
        if ($parameter instanceof Entity) {
            return;
        }

        throw new BadRequestException(
            'invalid_bound_argument_type',
            'The provided bound argument was not of the correct type for this function'
        );
    }
}
