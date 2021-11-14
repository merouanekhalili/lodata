<?php

namespace Flat3\Lodata\Tests\Unit\Operation;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Models\Flight;
use Flat3\Lodata\Tests\Operations\Service;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class DiscoveryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped();
        }

        Lodata::discoverEloquentModel(Flight::class);
        Lodata::discoverOperations(Service::class);
    }

    public function test_metadata()
    {
        $this->assertMetadataDocuments();
    }

    public function test_simple()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/hello()')
        );
    }

    public function test_identity()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path("/identity(arg='hello')")
        );
    }

    public function test_bind()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path("/add(a=1,b=1)/increment")
        );
    }

    public function test_action()
    {
        $this->assertNoContent(
            (new Request)
                ->post()
                ->path('/exec()')
        );
    }

    public function test_new_name()
    {
        $this->assertNoContent(
            (new Request)
                ->post()
                ->path('/exec2()')
        );
    }
}