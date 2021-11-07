<?php

namespace Flat3\Lodata\Tests\Unit\Queries\Entity;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\CollectionEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;

class NestingTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $type = new EntityType('a');
        $type->setKey(new DeclaredProperty('id', Type::int32()));
        $d = new ComplexType('d');
        $d->addDeclaredProperty('d', Type::string());

        $type->addProperty(new DeclaredProperty('b', Type::string()));
        $type->addProperty(new DeclaredProperty('c', $d));
        Lodata::add($type);

        $set = new CollectionEntitySet('atest', $type);
        $set->setCollection(
            collect(
                [
                    [
                        'b' => 'c',
                        'c' => [
                            'd' => 'e',
                            'dyni' => 4,
                        ],
                    ]
                ]
            )
        );

        Lodata::add($set);
    }

    public function test_schema()
    {
        $this->assertMetadataDocuments();
    }

    public function test_nested()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('atest')
        );
    }

    public function test_nested_path()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('atest(0)/c')
        );
    }

    public function test_double_nested_path()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('atest(0)/c/d')
        );
    }

    public function test_update_nested()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->patch()
                ->body([
                    'c' => [
                        'd' => 'q'
                    ]
                ])
                ->path('atest/0')
        );

        $this->assertMatchesSnapshot(Lodata::getEntitySet('atest')->getCollection());
    }

    public function test_update_nested_complex()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->patch()
                ->body([
                    'd' => 'q',
                ])
                ->path('atest/0/c')
        );

        $this->assertMatchesSnapshot(Lodata::getEntitySet('atest')->getCollection());
    }
}