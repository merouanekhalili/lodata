<?php

declare(strict_types=1);

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Helper\Gate;
use Flat3\Lodata\Interfaces\Operation\FunctionInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Operation;
use Illuminate\Http\Request;

class Function_ extends Operation implements FunctionInterface
{
    public function getKind(): string
    {
        return 'Function';
    }

    public function getClientArguments(): array
    {
        return $this->getInlineParameters();
    }

    public function execute(): ?PipeInterface
    {
        $this->transaction->assertMethod(
            Request::METHOD_GET,
            'This operation must be addressed with a GET request'
        );

        $arguments = $this->parseClientArguments();
        Gate::execute($this, $this->transaction, $arguments)->ensure();

        $result = $this->invoke($arguments);

        if ($result === null) {
            throw new InternalServerErrorException(
                'missing_function_result',
                'Function is required to return a result'
            );
        }

        $this->transaction->getRequest()->setMethod(Request::METHOD_GET);

        return $this->returnResult($result);
    }
}