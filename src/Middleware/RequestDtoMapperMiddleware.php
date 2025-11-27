<?php

declare(strict_types=1);

namespace MethorZ\Dto\Middleware;

use MethorZ\Dto\Attribute\MapRequestTo;
use MethorZ\Dto\Exception\MappingException;
use MethorZ\Dto\Exception\ValidationException;
use MethorZ\Dto\RequestDtoMapperInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use ReflectionException;

/**
 * Middleware that automatically maps requests to DTOs based on handler attributes
 *
 * Looks for the #[MapRequestTo] attribute on the matched handler and automatically
 * creates and validates the DTO, attaching it to the request.
 *
 * Handlers can then simply retrieve the DTO from the request:
 *   $dto = $request->getAttribute('dto');
 */
final readonly class RequestDtoMapperMiddleware implements MiddlewareInterface
{
    public function __construct(
        private RequestDtoMapperInterface $dtoMapper,
    ) {
    }

    /**
     * @throws MappingException
     * @throws ValidationException
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        // Get the handler's DTO mapping attribute
        $mapToAttribute = $this->getMapToAttribute($handler);

        // No attribute? Just pass through
        if ($mapToAttribute === null) {
            return $handler->handle($request);
        }

        try {
            // Map request to DTO
            $dto = $this->dtoMapper->map($mapToAttribute->dtoClass, $request);

            // Attach DTO to request
            $request = $request->withAttribute($mapToAttribute->attributeName, $dto);

            // Continue to handler with DTO attached
            return $handler->handle($request);
        } catch (ValidationException | MappingException $e) {
            // Let the error handler middleware catch these
            throw $e;
        }
    }

    /**
     * Extract MapRequestTo attribute from handler
     */
    private function getMapToAttribute(RequestHandlerInterface $handler): ?MapRequestTo
    {
        try {
            $reflection = new ReflectionClass($handler);
            $attributes = $reflection->getAttributes(MapRequestTo::class);

            if (empty($attributes)) {
                return null;
            }

            return $attributes[0]->newInstance();
        } catch (ReflectionException) {
            return null;
        }
    }
}
