<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Fixtures\Handler;

use MethorZ\Dto\Exception\MappingException;
use MethorZ\Dto\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface;

/**
 * Capturable error handler fixture for testing DtoHandlerWrapper
 */
final class TestErrorHandler
{
    public ValidationException|MappingException|null $capturedError = null;

    public function __construct(
        private readonly ResponseInterface $response,
    ) {
    }

    public function __invoke(ValidationException|MappingException $e): ResponseInterface
    {
        $this->capturedError = $e;

        return $this->response;
    }
}
