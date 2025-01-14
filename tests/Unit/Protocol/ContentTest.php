<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\Operation\FunctionInterface;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type\String_;

class ContentTest extends TestCase
{
    public function test_content_encoding()
    {
        Lodata::add(new class('exf1') extends Operation implements FunctionInterface {
            function invoke(Transaction $transaction): String_
            {
                $transaction->setContentEncoding('identity');
                return new String_('hello');
            }
        });

        $this->assertMetadataResponse(
            (new Request)
                ->path('/exf1()')
        );
    }

    public function test_content_language()
    {
        Lodata::add(new class('exf1') extends Operation implements FunctionInterface {
            function invoke(Transaction $transaction): String_
            {
                $transaction->setContentLanguage('fr');
                return new String_('bonjour');
            }
        });

        $this->assertMetadataResponse(
            (new Request)
                ->path('/exf1()')
        );
    }
}
