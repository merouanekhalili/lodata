<?php

namespace Flat3\Lodata\Tests\Unit\Clients;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Data\TestModels;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class ODCFFTest extends TestCase
{
    use TestModels;

    public function test_odcff()
    {
        $this->withFlightModel();
        $this->assertHtmlResponse(
            (new Request)
                ->path('/_lodata/airports.odc')
        );
    }

    public function test_odcff_missing()
    {
        $this->assertNotFoundException(
            (new Request)
                ->path('/_lodata/missing.odc')
        );
    }

    public function test_odcff_url()
    {
        $this->assertEquals('http://localhost/odata/_lodata/Flights.odc', Lodata::getOdcUrl('Flights'));
    }
}