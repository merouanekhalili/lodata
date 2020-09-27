<?php

namespace Flat3\OData\Tests\Unit\Queries\EntitySet;

use Flat3\OData\Tests\Data\FlightDataModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class EntitySetTest extends TestCase
{
    use FlightDataModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightDataModel();
    }

    public function test_read_an_entity_set()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
        );
    }
}