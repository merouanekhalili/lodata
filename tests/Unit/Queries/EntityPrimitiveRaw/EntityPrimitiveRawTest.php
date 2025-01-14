<?php

namespace Flat3\Lodata\Tests\Unit\Queries\EntityPrimitiveRaw;

use Flat3\Lodata\Tests\Models\Flight;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class EntityPrimitiveRawTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_read_an_entity_set_primitive_raw()
    {
        $this->assertTextMetadataResponse(
            (new Request)
                ->text()
                ->path('/flights(1)/id/$value')
        );
    }

    public function test_null_raw_not_found()
    {
        $flight = (new Flight([
            'origin' => null,
        ]));
        $flight->save();

        $this->assertNotFound(
            (new Request)
                ->text()
                ->path('/flights(0)/origin/$value')
        );
    }

    public function test_null_raw_no_content()
    {
        $flight = (new Flight([
            'origin' => null,
        ]));
        $flight->save();

        $this->assertNoContent(
            (new Request)
                ->text()
                ->path('/flights('.$flight->id.')/origin/$value')
        );
    }

    public function test_raw_custom_accept()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->header('accept', 'application/octet-stream')
                ->path('/flights(1)/id/$value')
        );
    }

    public function test_raw_custom_format()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->query('$format', 'application/octet-stream')
                ->path('/flights(1)/id/$value')
        );
    }
}
