<?php

declare(strict_types=1);

namespace MethorZ\Dto\Middleware;

use Laminas\Diactoros\Response\JsonResponse;
use MethorZ\Dto\Response\JsonSerializableDto;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Automatic JSON Response Middleware
 *
 * Automatically converts Response DTOs to JSON responses.
 *
 * This middleware allows handlers to return DTOs directly instead of
 * ResponseInterface objects. Any object implementing JsonSerializableDto
 * will be automatically converted to a JsonResponse with the appropriate
 * status code.
 *
 * Benefits:
 * - Handlers become simpler (return DTOs, not ResponseInterface)
 * - Perfect type safety (handler signature specifies exact DTO type)
 * - DTOs control their own serialization and status code
 * - Consistent with automatic request DTO injection
 *
 * Usage:
 * ```php
 * // In handler:
 * public function __invoke(
 *     ServerRequestInterface $request,
 *     CreateItemRequest $dto
 * ): ItemResponse {  // ← Returns DTO, not ResponseInterface!
 *     return $this->service->execute($dto);
 * }
 * ```
 *
 * The middleware will automatically convert ItemResponse to JsonResponse.
 */
final readonly class AutoJsonResponseMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        // If handler returned a DTO, convert to JsonResponse
        if ($response instanceof JsonSerializableDto) {
            return new JsonResponse(
                $response->jsonSerialize(),
                $response->getStatusCode(),
            );
        }

        // If already a ResponseInterface, pass through unchanged
        return $response;
    }
}
