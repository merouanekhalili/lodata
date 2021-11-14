<?php

namespace Flat3\Lodata\Tests\Unit\Operation;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type\Int32;
use Flat3\Lodata\Type\String_;

class ActionTest extends TestCase
{
    public function test_get_not_allowed()
    {
        $exa1 = new Operation\Action('exa1');
        $exa1->setCallable(function (): String_ {
            return new String_('hello');
        });
        Lodata::add($exa1);

        $this->assertMethodNotAllowed(
            (new Request)
                ->path('/exa1()')
        );
    }

    public function test_callback()
    {
        $exa1 = new Operation\Action('exa1');
        $exa1->setCallable(function (): String_ {
            return new String_('hello');
        });
        Lodata::add($exa1);

        $this->assertJsonResponse(
            (new Request)
                ->post()
                ->path('/exa1()')
        );
    }

    public function test_service_document()
    {
        $exa1 = new Operation\Action('exa1');
        $exa1->setCallable(function (): String_ {
            return new String_('hello');
        });
        Lodata::add($exa1);

        $this->assertJsonResponse(
            (new Request)
        );
    }

    public function test_callback_entity()
    {
        $this->assertNotFound(
            (new Request)
                ->path('/exa2()')
        );
    }

    public function test_no_composition()
    {
        $textv1 = new Operation\Action('textv1');
        $textv1->setCallable(function (): Int32 {
            return new Int32(3);
        });
        Lodata::add($textv1);

        $this->assertBadRequest(
            (new Request)
                ->post()
                ->path('/textv1()/$value')
        );
    }

    public function test_void_callback()
    {
        $textv1 = new Operation\Action('textv1');
        $textv1->setCallable(function (): void {
        });
        Lodata::add($textv1);

        $this->assertNoContent(
            (new Request)
                ->post()
                ->path('/textv1()')
        );
    }

    public function test_default_null_callback()
    {
        $textv1 = new Operation\Action('textv1');
        $textv1->setCallable(function () {
        });
        Lodata::add($textv1);

        $this->assertNoContent(
            (new Request)
                ->post()
                ->path('/textv1()')
        );
    }

    public function test_explicit_null_callback()
    {
        $textv1 = new Operation\Action('textv1');
        $textv1->setCallable(function () {
            return null;
        });
        Lodata::add($textv1);

        $this->assertNoContent(
            (new Request)
                ->post()
                ->path('/textv1()')
        );
    }

    public function test_bound()
    {
        $this->withFlightModel();

        $aa1 = new Operation\Action('aa1');
        $aa1->setCallable(function (Entity $airport): Entity {
            return $airport;
        });
        $aa1->setBindingParameterName('airport');
        $aa1->setReturnType(Lodata::getEntityType('airport'));
        Lodata::add($aa1);

        $this->assertJsonResponse(
            (new Request)
                ->post()
                ->path('/airports(1)/aa1')
        );
    }

    public function test_create()
    {
        $this->withFlightModel();

        $aa1 = new Operation\Action('aa1');
        $aa1->setCallable(function (EntitySet $airports, Transaction $transaction): Entity {
            $transaction->getResponse()->setStatusCode(Response::HTTP_CREATED);

            $entity = $airports->newEntity();
            $entity->setEntityId(4);

            return $entity;
        });
        $aa1->setReturnType(Lodata::getEntityType('airport'));
        Lodata::add($aa1);

        $this->assertJsonResponse(
            (new Request)
                ->post()
                ->path('/airports/aa1'),
            Response::HTTP_CREATED
        );
    }

    public function test_parameters()
    {
        $aa1 = new Operation\Action('aa1');
        $aa1->setCallable(function (Int32 $a, Int32 $b): Int32 {
            return new Int32($a->get() + $b->get());
        });
        Lodata::add($aa1);

        $this->assertJsonResponse(
            (new Request)
                ->post()
                ->body([
                    'a' => 3,
                    'b' => 4,
                ])
                ->path('/aa1')
        );
    }

    public function test_prefers_no_results()
    {
        $aa1 = new Operation\Action('aa1');
        $aa1->setCallable(function (): Int32 {
            return new Int32(99);
        });
        Lodata::add($aa1);

        $this->assertNoContent(
            (new Request)
                ->post()
                ->body([
                    'a' => 3,
                    'b' => 4,
                ])
                ->path('/aa1')
                ->header('Prefer', 'return=minimal')
        );
    }

    public function test_parameters_invalid_body_string()
    {
        $aa1 = new Operation\Action('aa1');
        $aa1->setCallable(function (Int32 $a, Int32 $b): Int32 {
            return new Int32($a->get() + $b->get());
        });
        Lodata::add($aa1);

        $this->assertNotAcceptable(
            (new Request)
                ->post()
                ->body('[d')
                ->path('/aa1')
        );
    }

    public function test_parameters_invalid_body_array()
    {
        $this->withFlightModel();

        $aa1 = new Operation\Action('aa1');
        $aa1->setCallable(function (Int32 $a, Int32 $b): Int32 {
            return new Int32($a->get() + $b->get());
        });
        Lodata::add($aa1);

        $this->assertBadRequest(
            (new Request)
                ->post()
                ->header('content-type', 'application/json')
                ->body('[d')
                ->path('/aa1')
        );
    }

    public function test_null_typed_callback()
    {
        $booleanv1 = new Operation\Action('booleanv1');
        $booleanv1->setCallable(function (): ?bool {
            return null;
        });
        Lodata::add($booleanv1);

        $this->assertMetadataDocuments();

        $this->assertNoContent(
            (new Request)
                ->post()
                ->path('/booleanv1()')
        );
    }
}