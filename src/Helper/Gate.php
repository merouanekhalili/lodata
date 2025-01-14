<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Protocol\ForbiddenException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Illuminate\Support\Facades\Gate as LaravelGate;

/**
 * Gate
 * @package Flat3\Lodata\Helper
 */
final class Gate
{
    const read = 'read';
    const create = 'create';
    const delete = 'delete';
    const update = 'update';
    const query = 'query';
    const execute = 'execute';

    protected $access;
    protected $resource;
    protected $arguments;
    protected $transaction;

    public function __construct(ResourceInterface $resource, Transaction $transaction)
    {
        $this->resource = $resource;
        $this->transaction = $transaction;
    }

    public static function read(ResourceInterface $resource, Transaction $transaction): Gate
    {
        return (new self($resource, $transaction))->setAccess(self::read);
    }

    public static function create(ResourceInterface $resource, Transaction $transaction): Gate
    {
        return (new self($resource, $transaction))->setAccess(self::create);
    }

    public static function delete(ResourceInterface $resource, Transaction $transaction): Gate
    {
        return (new self($resource, $transaction))->setAccess(self::delete);
    }

    public static function update(ResourceInterface $resource, Transaction $transaction): Gate
    {
        return (new self($resource, $transaction))->setAccess(self::update);
    }

    public static function query(ResourceInterface $resource, Transaction $transaction): Gate
    {
        return (new self($resource, $transaction))->setAccess(self::query);
    }

    public static function execute(ResourceInterface $resource, Transaction $transaction, array $arguments): Gate
    {
        return (new self($resource, $transaction))->setAccess(self::execute)->setArguments($arguments);
    }

    /**
     * Set the transaction
     * @param  Transaction  $transaction
     * @return $this
     */
    public function setTransaction(Transaction $transaction): self
    {
        $this->transaction = $transaction;

        return $this;
    }

    /**
     * Set the resource
     * @param  ResourceInterface  $resource
     * @return $this
     */
    public function setResource(ResourceInterface $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Set the access type
     * @param  string  $access
     * @return $this
     */
    public function setAccess(string $access): self
    {
        if (!in_array(
            $access,
            [self::read, self::create, self::update, self::delete, self::query, self::execute]
        )) {
            throw new InternalServerErrorException('invalid_access', 'The access type requested is not valid');
        }

        $this->access = $access;

        return $this;
    }

    /**
     * Set the operation arguments
     * @param  array  $arguments
     * @return $this
     */
    public function setArguments(array $arguments): self
    {
        $this->arguments = $arguments;

        return $this;
    }

    /**
     * Get the transaction attached to this gate
     * @return Transaction
     */
    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    /**
     * Get the resource attached to this gate
     * @return ResourceInterface
     */
    public function getResource(): ResourceInterface
    {
        return $this->resource;
    }

    /**
     * Get the type of access this gate represents
     * @return string
     */
    public function getAccess(): string
    {
        return $this->access;
    }

    /**
     * Get the operation arguments attached to this gate
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Check if this gate is allowed, returning the response
     * @return bool
     */
    public function allows(): bool
    {
        try {
            $this->ensure();
            return true;
        } catch (ForbiddenException $e) {
            return false;
        }
    }

    /**
     * Ensure this gate is allowed, throwing an exception if not
     */
    public function ensure(): void
    {
        if (!in_array($this->access, [self::read, self::query]) && config('lodata.readonly') === true) {
            throw new ForbiddenException('forbidden', 'This service is read-only');
        }

        if (config('lodata.authorization') === false) {
            return;
        }

        if (LaravelGate::denies('lodata', $this)) {
            throw new ForbiddenException('forbidden', 'This request is not permitted');
        }
    }
}