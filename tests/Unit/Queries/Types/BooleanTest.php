<?php

namespace Flat3\Lodata\Tests\Unit\Queries\Types;

use Flat3\Lodata\Tests\Request;

class BooleanTest extends TypeTest
{
    public function test_filter_boolean_eq_true()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->filter('is_big eq true')
                ->select('id,is_big')
        );
    }

    public function test_filter_boolean_ne_true()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->filter('is_big ne true')
                ->select('id,is_big')
        );
    }

    public function test_filter_boolean_eq_false()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->filter('is_big eq false')
                ->select('id,is_big')
        );
    }

    public function test_filter_boolean_ne_false()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->filter('is_big ne false')
                ->select('id,is_big')
        );
    }
}
