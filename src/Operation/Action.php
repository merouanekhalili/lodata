<?php

declare(strict_types=1);

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\NoContentException;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\Gate;
use Flat3\Lodata\Interfaces\Operation\ActionInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Operation;
use Illuminate\Http\Request;

class Action extends Operation implements ActionInterface
{
    public function getKind(): string
    {
        return 'Action';
    }

    public function execute(): ?PipeInterface
    {
        $this->transaction->assertMethod(
            Request::METHOD_POST,
            'This operation must be addressed with a POST request'
        );

        if ($this->transaction->getBody()) {
            $this->transaction->assertContentTypeJson();
        }

        $arguments = $this->parseClientArguments();

        Gate::execute($this, $this->transaction, $arguments)->ensure();

        $result = $this->invoke($arguments);

        $returnPreference = $this->transaction->getPreferenceValue(Constants::return);

        if ($returnPreference === Constants::minimal) {
            throw (new NoContentException)
                ->header(Constants::preferenceApplied, Constants::return.'='.Constants::minimal);
        }

        $this->transaction->getRequest()->setMethod(Request::METHOD_GET);

        return $this->returnResult($result);
    }

    public function getClientArguments(): array
    {
        $body = $this->transaction->getBody();

        if ($body && !is_array($body)) {
            throw new BadRequestException(
                'invalid_action_arguments',
                'The arguments to the action were not correctly formed as an array'
            );
        }

        return $body ?: [];
    }
}