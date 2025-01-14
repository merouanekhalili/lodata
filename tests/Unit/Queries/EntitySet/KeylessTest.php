<?php

namespace Flat3\Lodata\Tests\Unit\Queries\EntitySet;

use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class KeylessTest extends TestCase
{
    public function test_discover()
    {
        $this->withNamesDatabase();

        $set = new SQLEntitySet('names', new EntityType('name'));
        $set->discoverProperties();
        Lodata::add($set);

        $this->assertJsonResponse(
            (new Request)
                ->path('/names')
        );
    }

    public function test_query()
    {
        $this->withNamesModel();

        $this->assertJsonResponse(
            (new Request)
                ->path('/names')
        );
    }

    public function test_read()
    {
        $this->withNamesModel();

        $this->assertNotFound(
            (new Request)
                ->path('/names/1')
        );
    }

    public function test_delete()
    {
        $this->withNamesModel();

        $this->assertNotFound(
            (new Request)
                ->delete()
                ->path('/names/1')
        );
    }

    public function test_create()
    {
        $this->withNamesModel();

        $this->assertBadRequest(
            (new Request)
                ->post()
                ->path('/names')
                ->body([
                    'first_name' => 'felix',
                    'last_name' => 'micro',
                ])
        );
    }

    public function test_update()
    {
        $this->withNamesModel();

        $this->assertNotFound(
            (new Request)
                ->post()
                ->path('/names/1')
                ->body([
                    'first_name' => 'felix',
                    'last_name' => 'micro',
                ])
        );
    }

    public function test_paginate()
    {
        $this->withNamesModel();

        $this->assertJsonResponse(
            (new Request)
                ->query('top', 1)
                ->path('/names')
        );

        $this->assertJsonResponse(
            (new Request)
                ->query('top', 1)
                ->query('skip', 1)
                ->path('/names')
        );
    }

    public function test_metadata()
    {
        $this->withNamesModel();
        $this->assertMetadataDocuments();
    }
}