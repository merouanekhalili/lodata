<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\LexerException;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\Arguments;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\AnnotationInterface;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\Operation\ActionInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Interfaces\ServiceInterface;
use Flat3\Lodata\Operation\Argument;
use Flat3\Lodata\Operation\EntityArgument;
use Flat3\Lodata\Operation\EntitySetArgument;
use Flat3\Lodata\Operation\PrimitiveArgument;
use Flat3\Lodata\Operation\TransactionArgument;
use Flat3\Lodata\Operation\ValueArgument;
use Flat3\Lodata\Traits\HasAnnotations;
use Flat3\Lodata\Traits\HasIdentifier;
use Flat3\Lodata\Traits\HasTitle;
use Flat3\Lodata\Traits\HasTransaction;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Operation
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530382
 * @package Flat3\Lodata
 */
abstract class Operation implements ServiceInterface, ResourceInterface, IdentifierInterface, PipeInterface, AnnotationInterface
{
    use HasIdentifier;
    use HasTitle;
    use HasTransaction;
    use HasAnnotations;

    /** @var callable $callable */
    protected $callable;

    /**
     * The name of the binding parameter used in the invocation function
     * @var string $bindingParameterName
     */
    protected $bindingParameterName;

    /**
     * The OData return type from this operation
     * @var Type $returnType
     */
    protected $returnType;

    /**
     * The instance of the bound parameter provided to this operation instance
     * @var ?PipeInterface $boundParameter
     */
    private $boundParameter;

    /**
     * The URL inline parameters being provided to this operation instance
     * @var array $inlineParameters
     */
    private $inlineParameters = [];

    public function __construct($identifier)
    {
        $this->setIdentifier($identifier);
    }

    /**
     * Execute the operation and return the result
     * @return PipeInterface|null
     */
    abstract public function execute(): ?PipeInterface;

    /**
     * Get the OData kind of this operation
     * @return string Kind
     */
    abstract public function getKind(): string;

    /**
     * Get the arguments being provided by the client
     * @return array Arguments
     */
    abstract public function getClientArguments(): array;

    public function returnResult($result): ?PipeInterface
    {
        $returnType = $this->getReturnType();

        if ($result === null && !$this->isNullable()) {
            throw new InternalServerErrorException(
                'invalid_null_returned',
                'The operation returned null but the result is not nullable'
            );
        }

        if ($returnType instanceof EntityType && !$result->getType() instanceof $returnType) {
            throw new InternalServerErrorException(
                'invalid_entity_type_returned',
                'The operation returned an entity type that did not match its defined type',
            );
        }

        if ($returnType instanceof PrimitiveType && !$result instanceof Primitive) {
            return $returnType->instance($result);
        }

        return $result;
    }

    /**
     * Return the attached callable
     * @return callable
     */
    public function getCallable()
    {
        $callable = $this->callable;

        if (is_callable($callable)) {
            return $callable;
        }

        if (is_array($callable)) {
            list($instance, $method) = $callable;

            if (is_string($instance) && class_exists($instance)) {
                $instance = App::make($instance);
            }

            return [$instance, $method];
        }

        return $callable;
    }

    /**
     * Set the operation callable
     * @param  callable  $callable
     * @return $this
     */
    public function setCallable($callable): self
    {
        $this->callable = $callable;

        return $this;
    }

    /**
     * Get the return type of this operation, based on reflection of the invocation method
     * @return string Return type
     */
    public function getCallableReturnType(): string
    {
        $rfc = $this->getCallableMethod();

        /** @var ReflectionNamedType $rt */
        $rt = $rfc->getReturnType();

        if (null === $rt) {
            return 'void';
        }

        return $rt->getName();
    }

    /**
     * Whether the result of this operation is a collection
     * @return bool
     */
    public function returnsCollection(): bool
    {
        $returnType = $this->getCallableReturnType();

        return $returnType === 'array' || is_a($returnType, EntitySet::class, true);
    }

    /**
     * Whether the result of this operation can be null
     * @return bool
     */
    public function isNullable(): bool
    {
        $rfn = $this->getCallableMethod();
        return !$rfn->hasReturnType() || $rfn->getReturnType()->allowsNull() || $rfn->getReturnType()->getName() === 'void';
    }

    public function getCallableMethod(): ReflectionFunctionAbstract
    {
        $callable = $this->getCallable();

        if (!$callable) {
            throw new InternalServerErrorException(
                'missing_callable',
                'The operation has no callable',
            );
        }

        if (is_array($callable)) {
            list($instance, $method) = $callable;
            return new ReflectionMethod($instance, $method);
        }

        return new ReflectionFunction($callable);
    }

    /**
     * Get the method parameter name of the binding parameter used on the invocation method
     * @return string|null Binding parameter name
     */
    public function getBindingParameterName(): ?string
    {
        return $this->bindingParameterName;
    }

    public function isBound(): bool
    {
        return !!$this->bindingParameterName;
    }

    /**
     * Set the name of the invocation method parameter used to receive the binding parameter
     * @param  string  $bindingParameterName  Binding parameter name
     * @return $this
     */
    public function setBindingParameterName(string $bindingParameterName): self
    {
        $this->bindingParameterName = $bindingParameterName;

        return $this;
    }

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        $lexer = new Lexer($currentSegment);

        try {
            $operationIdentifier = $lexer->identifier();
        } catch (LexerException $e) {
            throw new PathNotHandledException();
        }

        $operation = Lodata::getOperation($operationIdentifier);

        if (!$operation instanceof Operation) {
            throw new PathNotHandledException();
        }

        if ($nextSegment && $operation instanceof ActionInterface) {
            throw new BadRequestException(
                'cannot_compose_action',
                'It is not permitted to further compose the result of an action'
            );
        }

        if (!$argument && $operation->isBound()) {
            throw new BadRequestException(
                'missing_bound_argument',
                'This operation is bound, but no bound argument was provided'
            );
        }

        try {
            $inlineParameters = $lexer->operationParameters();
        } catch (LexerException $e) {
            throw new BadRequestException(
                'invalid_arguments',
                'The arguments provided to the operation were not valid'
            );
        }

        array_walk($inlineParameters, function (&$value) use ($transaction) {
            if (Str::startsWith($value, '@')) {
                $value = $transaction->getParameterAlias($value);
            }
        });

        if (!$nextSegment) {
            /** @var Argument $callableArgument */
            foreach ($operation->getCallableArguments() as $callableArgument) {
                $value = $transaction->getImplicitParameterAlias($callableArgument->getName());

                if (!$value) {
                    continue;
                }

                $inlineParameters[$callableArgument->getName()] = $value;
            }
        }

        $operation = clone $operation;
        $operation->setTransaction($transaction);
        $operation->setBoundParameter($argument);
        $operation->setInlineParameters($inlineParameters);

        return $operation->execute();
    }

    /**
     * Get the resource URL of this operation instance
     * @param  Transaction  $transaction  Related transaction
     * @return string Resource URL
     */
    public function getResourceUrl(Transaction $transaction): string
    {
        return $transaction->getResourceUrl().$this->getName();
    }

    /**
     * Get the OData return type of this operation
     * @return Type|null Return type
     */
    public function getReturnType(): ?Type
    {
        if ($this->returnType) {
            return $this->returnType;
        }

        $rrt = $this->getCallableReturnType();

        if (is_a($rrt, Primitive::class, true)) {
            return new PrimitiveType($rrt);
        }

        return Type::castInternalType($rrt);
    }

    /**
     * Set the OData type that will be returned by this operation
     * @param  Type  $type  Return type
     * @return $this
     */
    public function setReturnType(Type $type): self
    {
        $this->returnType = $type;

        return $this;
    }

    /**
     * Retrieve the bound parameter attached to this operation
     * @return PipeInterface|null
     */
    public function getBoundParameter(): ?PipeInterface
    {
        $this->assertTransaction();

        return $this->boundParameter;
    }

    /**
     * Set the bound parameter on an instance of this operation
     * @param  mixed  $parameter  Binding parameter
     * @return $this
     */
    public function setBoundParameter($parameter): self
    {
        $this->assertTransaction();

        if ($parameter instanceof PropertyValue) {
            $parameter = $parameter->getValue();
        }

        $this->boundParameter = $parameter;
        return $this;
    }

    public function getInlineParameters(): array
    {
        $this->assertTransaction();

        return $this->inlineParameters;
    }

    /**
     * Set the URL inline parameters on an instance of this operation
     * @param  array  $inlineParameters  Inline parameters
     * @return $this
     */
    public function setInlineParameters(array $inlineParameters): self
    {
        $this->inlineParameters = $inlineParameters;

        return $this;
    }

    /**
     * Extract operation arguments for metadata
     * Ensure the binding parameter is first, if it exists. Filter out non-odata arguments.
     * @return Arguments|Argument[]
     */
    public function getMetadataArguments()
    {
        return $this->getCallableArguments()->sort(function (Argument $a, Argument $b) {
            if ($a->getName() === $this->getBindingParameterName()) {
                return -1;
            }

            if ($b->getName() === $this->getBindingParameterName()) {
                return 1;
            }

            return 0;
        })->filter(function ($argument) {
            if ($argument instanceof PrimitiveArgument) {
                return true;
            }

            if (($argument instanceof EntitySetArgument || $argument instanceof EntityArgument) && $this->getBindingParameterName() === $argument->getName()) {
                return true;
            }

            return false;
        });
    }

    /**
     * Get the reflected arguments of the invocation of this operation
     * @return Argument[]|Arguments Arguments
     */
    public function getCallableArguments(): Arguments
    {
        $rfn = $this->getCallableMethod();
        $args = new Arguments();

        foreach ($rfn->getParameters() as $parameter) {
            /** @var ReflectionNamedType $namedType */
            $namedType = $parameter->getType();
            $typeName = $namedType->getName();

            switch (true) {
                case is_a($typeName, EntitySet::class, true):
                    $args[] = new EntitySetArgument($parameter);
                    break;

                case is_a($typeName, Transaction::class, true):
                    $args[] = new TransactionArgument($parameter);
                    break;

                case is_a($typeName, Entity::class, true):
                    $args[] = new EntityArgument($parameter);
                    break;

                case is_a($typeName, Primitive::class, true):
                    $args[] = new PrimitiveArgument($parameter);
                    break;

                default:
                    $args[] = new ValueArgument($parameter);
                    break;
            }
        }

        return $args;
    }

    /**
     * Parse and return arguments from the client
     * @return array Arguments
     */
    public function parseClientArguments(): array
    {
        $clientArguments = $this->getClientArguments();
        $bindingParameterName = $this->getBindingParameterName();
        $callableArguments = $this->getCallableArguments();

        if ($bindingParameterName && !$callableArguments->exists($bindingParameterName)) {
            throw new InternalServerErrorException(
                'missing_callable_binding_parameter',
                'The provided callable did not have a argument named '.$bindingParameterName
            );
        }

        $arguments = [];

        /** @var Argument $argument */
        foreach ($callableArguments as $argument) {
            $argumentName = $argument->getName();
            $clientArgument = $clientArguments[$argumentName] ?? null;

            if ($bindingParameterName === $argumentName) {
                $argument->assertValidParameter($this->boundParameter);
                $clientArgument = $this->boundParameter;
            }

            switch (true) {
                case $argument instanceof TransactionArgument:
                case $argument instanceof EntitySetArgument:
                    $arguments[] = $argument->generate($this->transaction);
                    break;

                case $argument instanceof ValueArgument:
                    $arguments[] = $argument->generate($clientArgument)->get();
                    break;

                case $argument instanceof PrimitiveArgument:
                case $argument instanceof EntityArgument:
                    $arguments[] = $argument->generate($clientArgument);
                    break;
            }
        }

        return $arguments;
    }

    /**
     * Invoke the provided callable
     * @param  array  $arguments
     * @return mixed
     */
    public function invoke(array $arguments = [])
    {
        return call_user_func_array($this->getCallable(), array_values($arguments));
    }
}
