<?php

declare(strict_types=1);

namespace MethorZ\Dto\Middleware;

use MethorZ\Dto\Attribute\MapRequestTo;
use MethorZ\Dto\Handler\DtoHandlerInterface;
use MethorZ\Dto\RequestDtoMapperInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

use function count;

/**
 * Middleware that automatically injects DTOs as parameters to handler's __invoke() method
 *
 * For handlers implementing DtoHandlerInterface, this middleware:
 * 1. Inspects the __invoke() method signature
 * 2. Determines which DTO class is needed (from type hint or #[MapRequestTo] attribute)
 * 3. Maps the request to that DTO
 * 4. Calls __invoke() with the DTO as a parameter
 *
 * This allows handlers to receive DTOs directly as parameters instead of
 * retrieving them from the request.
 */
final readonly class DtoParameterInjectionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private RequestDtoMapperInterface $dtoMapper,
    ) {
    }

    /**
     * @throws \MethorZ\Dto\Exception\MappingException
     * @throws \MethorZ\Dto\Exception\ValidationException
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        // Only process handlers that implement DtoHandlerInterface
        if (! $handler instanceof DtoHandlerInterface) {
            return $handler->handle($request);
        }

        try {
            // Get the DTO class from the handler's __invoke() signature
            $dtoClass = $this->extractDtoClass($handler);

            if ($dtoClass === null) {
                // No DTO parameter found, fallback to standard handle()
                return $handler->handle($request);
            }

            // Map request to DTO
            $dto = $this->dtoMapper->map($dtoClass, $request);

            // Call handler with DTO injected as parameter
            return $handler->__invoke($request, $dto);
        } catch (ReflectionException) {
            // Fallback to normal handling if reflection fails
            return $handler->handle($request);
        }
    }

    /**
     * Extract the DTO class from handler's __invoke() method signature
     *
     * Looks for:
     * 1. Second parameter's type hint (the DTO class)
     * 2. #[MapRequestTo] attribute on the class
     *
     * @return class-string|null
     * @throws \ReflectionException
     */
    private function extractDtoClass(DtoHandlerInterface $handler): ?string
    {
        $reflection = new ReflectionClass($handler);

        // First, try to get from #[MapRequestTo] attribute
        $attributes = $reflection->getAttributes(MapRequestTo::class);
        if (! empty($attributes)) {
            $attribute = $attributes[0]->newInstance();
            return $attribute->dtoClass;
        }

        // Second, try to get from __invoke() method signature
        if (! $reflection->hasMethod('__invoke')) {
            return null;
        }

        $invokeMethod = $reflection->getMethod('__invoke');
        $parameters   = $invokeMethod->getParameters();

        // __invoke() should have at least 2 parameters: (ServerRequestInterface $request, DtoClass $dto)
        if (count($parameters) < 2) {
            return null;
        }

        // Get the second parameter (the DTO)
        $dtoParameter = $parameters[1];
        $dtoType      = $dtoParameter->getType();

        if (! $dtoType instanceof ReflectionNamedType || $dtoType->isBuiltin()) {
            return null;
        }

        $className = $dtoType->getName();

        // Ensure it's a valid class
        if (!class_exists($className)) {
            return null;
        }

        /** @var class-string $className */
        return $className;
    }
}
