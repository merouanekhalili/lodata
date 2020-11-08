<?php

namespace Flat3\Lodata\Expression;

/**
 * Event
 * @package Flat3\Lodata\Expression
 */
abstract class Event
{
    /**
     * AST node
     * @var Node $node Node
     * @internal
     */
    private $node;

    public function __construct(?Node $node = null)
    {
        $this->node = $node;
    }

    /**
     * Get the value of the attached AST node
     * @return mixed|null Value
     */
    public function getValue()
    {
        return $this->node->getValue();
    }

    /**
     * Get the attached AST node
     * @return Node|null Node
     */
    public function getNode()
    {
        return $this->node;
    }
}
