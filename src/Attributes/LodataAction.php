<?php

declare(strict_types=1);

namespace Flat3\Lodata\Attributes;

use Attribute;
use Flat3\Lodata\Operation\Action;

#[Attribute(Attribute::TARGET_METHOD)]
class LodataAction extends LodataOperation
{
    const operationClass = Action::class;
}