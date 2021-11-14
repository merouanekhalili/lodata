<?php

declare(strict_types=1);

namespace Flat3\Lodata\Attributes;

use Attribute;
use Flat3\Lodata\Operation\Function_;

#[Attribute(Attribute::TARGET_METHOD)]
class LodataFunction extends LodataOperation
{
    const operationClass = Function_::class;
}