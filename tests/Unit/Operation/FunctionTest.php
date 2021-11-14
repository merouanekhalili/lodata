<?php

namespace Flat3\Lodata\Tests\Unit\Operation;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Drivers\StaticEntitySet;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Tests\Data\Airport;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type\Int32;
use Flat3\Lodata\Type\String_;

class FunctionTest extends TestCase
{
    public function test_callback()
    {
        $op = new Operation\Function_('exf1');
        $op->setCallable(function (): String_ {
            return new String_('hello');
        });
        Lodata::add($op);

        $this->assertJsonResponse(
            (new Request)
                ->path('/exf1()')
        );
    }

    public function test_callback_no_parentheses()
    {
        $op = new Operation\Function_('exf1');
        $op->setCallable(function (): String_ {
            return new String_('hello');
        });
        Lodata::add($op);

        $this->assertJsonResponse(
            (new Request)
                ->path('/exf1')
        );
    }

    public function test_service_document()
    {
        $op = new Operation\Function_('exf1');
        $op->setCallable(function (): String_ {
            return new String_('hello');
        });
        Lodata::add($op);

        $this->assertJsonResponse(
            (new Request)
        );
    }

    public function test_callback_entity()
    {
        $this->withFlightModel();
        $op = new Operation\Function_('exf3');
        $op->setCallable(function (String_ $code): Entity {
            $airport = new Airport();
            $airport->setType(Lodata::getEntityType('airport'));
            $airport['code'] = $code->get();
            return $airport;
        });
        $op->setReturnType(Lodata::getEntityType('airport'));
        Lodata::add($op);

        $this->assertJsonResponse(
            (new Request)
                ->path("/exf3(code='xyz')")
        );
    }

    public function test_callback_entity_set()
    {
        $this->withTextModel();

        $op = new Operation\Function_('textf1');
        $op->setCallable(function (EntitySet $texts): EntitySet {
            return $texts;
        });
        $op->setReturnType(Lodata::getEntityType('text'));
        Lodata::add($op);

        $this->assertJsonResponse(
            (new Request)
                ->path('/textf1()')
        );
    }

    public function test_with_arguments()
    {
        $this->withMathFunctions();

        $this->assertJsonResponse(
            (new Request)
                ->path('/add(a=3,b=4)')
        );
    }

    public function test_with_argument_order()
    {
        $this->withMathFunctions();

        $this->assertJsonResponse(
            (new Request)
                ->path('/div(a=3,b=4)')
        );

        $this->assertJsonResponse(
            (new Request)
                ->path('/div(b=3,a=4)')
        );
    }

    public function test_with_indirect_arguments()
    {
        $this->withMathFunctions();

        $this->assertJsonResponse(
            (new Request)
                ->path('/add(a=@c,b=@d)')
                ->query('@c', 1)
                ->query('@d', 2)
        );
    }

    public function test_with_single_indirect_argument()
    {
        $this->withMathFunctions();

        $this->assertJsonResponse(
            (new Request)
                ->path('/add(a=@c,b=@c)')
                ->query('@c', 1)
        );
    }

    public function test_with_missing_indirect_arguments()
    {
        $this->withMathFunctions();

        $this->assertBadRequest(
            (new Request)
                ->path('/add(a=@c,b=@e)')
                ->query('@c', 1)
                ->query('@d', 2)
        );
    }

    public function test_with_implicit_parameter_aliases()
    {
        $this->withMathFunctions();

        $this->assertJsonResponse(
            (new Request)
                ->path('/add')
                ->query('a', 1)
                ->query('b', 2)
        );
    }

    public function test_with_implicit_parameter_alias_matching_system_query_option()
    {
        $add = new Operation\Function_('add');
        $add->setCallable(function (Int32 $apply, Int32 $compute): Int32 {
            return new Int32($apply->get() + $compute->get());
        });
        Lodata::add($add);

        $this->assertJsonResponse(
            (new Request)
                ->path('/add')
                ->query('@apply', 1)
                ->query('@compute', 2)
        );
    }

    public function test_function_composition()
    {
        $identity = new Operation\Function_('identity');
        $identity->setCallable(function (Int32 $i): Int32 {
            return new Int32($i->get());
        });
        Lodata::add($identity);

        $increment = new Operation\Function_('increment');
        $increment->setCallable(function (Int32 $i): Int32 {
            return new Int32($i->get() + 1);
        });
        $increment->setBindingParameterName('i');
        Lodata::add($increment);

        $this->assertJsonResponse(
            (new Request)
                ->path('/identity(i=1)/increment/increment')
        );
    }

    public function test_callback_modified_flight_entity_set()
    {
        $this->withFlightModel();

        $ffn1 = new Operation\Function_('ffn1');
        $ffn1->setCallable(function (Transaction $transaction, EntitySet $flights): EntitySet {
            $transaction->getSelect()->setValue('origin');
            return $flights;
        });
        $ffn1->setReturnType(Lodata::getEntityType('flight'));
        Lodata::add($ffn1);

        $this->assertJsonResponse(
            (new Request)
                ->path('/ffn1()')
        );
    }

    public function test_callback_bound_entity_set()
    {
        $this->withFlightModel();

        $ffb1 = new Operation\Function_('ffb1');
        $ffb1->setCallable(function (EntitySet $flights): EntitySet {
            return $flights;
        });
        $ffb1->setBindingParameterName('flights');
        $ffb1->setReturnType(Lodata::getEntityType('flight'));
        Lodata::add($ffb1);

        $this->assertJsonResponse(
            (new Request)
                ->path('/flights/ffb1()')
        );
    }

    public function test_callback_bound_entity_set_with_filter()
    {
        $this->withFlightModel();

        $sorter = new Operation\Function_('sorter');
        $sorter->setCallable(function (String_ $field, EntitySet $airports): EntitySet {
            $result = new StaticEntitySet($airports->getType());
            $result->setIdentifier($airports->getIdentifier());

            foreach ($airports->query() as $airport) {
                $result[] = $airport;
            }

            $result->sort(function (Entity $a1, Entity $a2) use ($field) {
                return $a1[$field->get()]->getPrimitiveValue() <=> $a2[$field->get()]->getPrimitiveValue();
            });

            return $result;
        });

        $sorter->setBindingParameterName('airports');
        $sorter->setReturnType(Lodata::getEntityType('airport'));
        Lodata::add($sorter);

        $this->assertJsonResponse(
            (new Request)
                ->path("/airports/\$filter(is_big eq true)/sorter(field='construction_date')")
        );
    }

    public function test_callback_bound_entity()
    {
        $this->withFlightModel();

        $ffb1 = new Operation\Function_('ffb1');
        $ffb1->setCallable(function (Entity $flight): Entity {
            return $flight;
        });
        $ffb1->setBindingParameterName('flight');
        $ffb1->setReturnType(Lodata::getEntityType('flight'));
        Lodata::add($ffb1);

        $this->assertJsonResponse(
            (new Request)
                ->path('/flights(1)/ffb1()')
        );
    }

    public function test_callback_bound_primitive()
    {
        $this->withFlightModel();

        $ffb1 = new Operation\Function_('ffb1');
        $ffb1->setCallable(function (String_ $origin): String_ {
            return new String_(strtoupper($origin->get()));
        });
        $ffb1->setBindingParameterName('origin');
        Lodata::add($ffb1);

        $this->assertJsonResponse(
            (new Request)
                ->path('/flights(1)/origin/ffb1()')
        );
    }

    public function test_callback_bound_internal_type()
    {
        $identity = new Operation\Function_('id');
        $identity->setCallable(function (int $i): int {
            return $i;
        });
        Lodata::add($identity);

        $increment = new Operation\Function_('incr');
        $increment->setCallable(function (int $a): int {
            return $a + 1;
        });
        $increment->setBindingParameterName('a');
        Lodata::add($increment);

        $this->assertJsonResponse(
            (new Request)
                ->path('/id(i=1)/incr')
        );
    }

    public function test_void_callback()
    {
        $textv1 = new Operation\Function_('textv1');
        $textv1->setCallable(function (): void {
        });
        Lodata::add($textv1);

        $this->assertInternalServerError(
            (new Request)
                ->path('/textv1()')
        );
    }

    public function test_default_null_callback()
    {
        $textv1 = new Operation\Function_('textv1');
        $textv1->setCallable(function () {
        });
        Lodata::add($textv1);

        $this->assertInternalServerError(
            (new Request)
                ->path('/textv1()')
        );
    }

    public function test_string_callback()
    {
        $stringv1 = new Operation\Function_('stringv1');
        $stringv1->setCallable(function (): string {
            return 'hello world';
        });
        Lodata::add($stringv1);

        $this->assertMetadataDocuments();

        $this->assertJsonResponse(
            (new Request)
                ->path('/stringv1()')
        );
    }

    public function test_int_callback()
    {
        $intv1 = new Operation\Function_('intv1');
        $intv1->setCallable(function (): int {
            return 4;
        });
        Lodata::add($intv1);

        $this->assertMetadataDocuments();

        $this->assertJsonResponse(
            (new Request)
                ->path('/intv1()')
        );
    }

    public function test_float_callback()
    {
        $floatv1 = new Operation\Function_('floatv1');
        $floatv1->setCallable(function (): float {
            return 0.1;
        });
        Lodata::add($floatv1);

        $this->assertMetadataDocuments();

        $this->assertJsonResponse(
            (new Request)
                ->path('/floatv1()')
        );
    }

    public function test_boolean_callback()
    {
        $booleanv1 = new Operation\Function_('booleanv1');
        $booleanv1->setCallable(function (): bool {
            return true;
        });
        Lodata::add($booleanv1);

        $this->assertMetadataDocuments();

        $this->assertJsonResponse(
            (new Request)
                ->path('/booleanv1()')
        );
    }

    public function test_bad_null_argument()
    {
        $textv1 = new Operation\Function_('textv1');
        $textv1->setCallable(function (String_ $a) {
        });
        Lodata::add($textv1);

        $this->assertBadRequest(
            (new Request)
                ->path('/textv1()')
        );
    }

    public function test_bad_argument_type()
    {
        $textv1 = new Operation\Function_('textv1');
        $textv1->setCallable(function (String_ $a) {
        });
        Lodata::add($textv1);

        $this->assertBadRequest(
            (new Request)
                ->path('/textv1(a=4)')
        );
    }

    public function test_string_argument()
    {
        $stringv1 = new Operation\Function_('stringv1');
        $stringv1->setCallable(function (string $arg): string {
            return $arg;
        });
        Lodata::add($stringv1);

        $this->assertMetadataDocuments();

        $this->assertJsonResponse(
            (new Request)
                ->path("/stringv1(arg='hello world')")
        );
    }

    public function test_int_argument()
    {
        $intv1 = new Operation\Function_('intv1');
        $intv1->setCallable(function (int $arg): int {
            return $arg;
        });
        Lodata::add($intv1);

        $this->assertMetadataDocuments();

        $this->assertJsonResponse(
            (new Request)
                ->path('/intv1(arg=4)')
        );
    }

    public function test_float_argument()
    {
        $floatv1 = new Operation\Function_('floatv1');
        $floatv1->setCallable(function (float $arg): float {
            return $arg;
        });
        Lodata::add($floatv1);

        $this->assertMetadataDocuments();

        $this->assertJsonResponse(
            (new Request)
                ->path('/floatv1(arg=4.2)')
        );
    }

    public function test_boolean_argument()
    {
        $booleanv1 = new Operation\Function_('booleanv1');
        $booleanv1->setCallable(function (bool $arg): bool {
            return $arg;
        });
        Lodata::add($booleanv1);

        $this->assertMetadataDocuments();

        $this->assertJsonResponse(
            (new Request)
                ->path('/booleanv1(arg=true)')
        );
    }

    public function test_null_argument()
    {
        $booleanv1 = new Operation\Function_('booleanv1');
        $booleanv1->setCallable(function (string $a, ?bool $arg, string $b): string {
            return $a.$b;
        });
        Lodata::add($booleanv1);

        $this->assertMetadataDocuments();

        $this->assertJsonResponse(
            (new Request)
                ->path("/booleanv1(a='a',b='b')")
        );
    }
}