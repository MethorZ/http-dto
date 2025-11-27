<?php

declare(strict_types=1);

namespace MethorZ\Dto\Middleware;

use MethorZ\Dto\Exception\MappingException;
use MethorZ\Dto\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware that catches DTO mapping/validation exceptions and converts them to responses
 *
 * This middleware should be registered early in the pipeline to catch exceptions
 * thrown by RequestDtoMapperMiddleware.
 *
 * Requires a callable that converts exceptions to ResponseInterface:
 *   function (ValidationException|MappingException $e): ResponseInterface
 */
final readonly class DtoValidationErrorMiddleware implements MiddlewareInterface
{
    /**
     * @param callable(ValidationException|MappingException): ResponseInterface $errorHandler
     */
    public function __construct(
        private mixed $errorHandler,
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        try {
            return $handler->handle($request);
        } catch (ValidationException | MappingException $e) {
            return ($this->errorHandler)($e);
        }
    }
}
