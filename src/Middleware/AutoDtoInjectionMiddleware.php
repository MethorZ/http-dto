<?php

declare(strict_types=1);

namespace MethorZ\Dto\Middleware;

use MethorZ\Dto\Exception\MappingException;
use MethorZ\Dto\Exception\ValidationException;
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
 * Automatic DTO injection middleware
 *
 * This middleware automatically handles DTO mapping, validation, and injection:
 * 1. Detects handlers implementing DtoHandlerInterface
 * 2. Inspects __invoke() signature to determine DTO class
 * 3. Maps request to DTO
 * 4. Validates DTO
 * 5. Catches validation/mapping exceptions and converts to error responses
 * 6. Injects validated DTO as parameter to handler's __invoke()
 *
 * Usage: Just register this one middleware in your pipeline!
 */
final readonly class AutoDtoInjectionMiddleware implements MiddlewareInterface
{
    /**
     * @param callable(ValidationException|MappingException): ResponseInterface $errorHandler
     */
    public function __construct(
        private RequestDtoMapperInterface $dtoMapper,
        private mixed $errorHandler,
    ) {
    }

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

            // Map and validate request → DTO
            $dto = $this->dtoMapper->map($dtoClass, $request);

            // Call handler with DTO injected as parameter
            return $handler->__invoke($request, $dto);
        } catch (ValidationException | MappingException $e) {
            // Convert exception to error response
            return ($this->errorHandler)($e);
        } catch (ReflectionException) {
            // Fallback to normal handling if reflection fails
            return $handler->handle($request);
        }
    }

    /**
     * Extract the DTO class from handler's __invoke() method signature
     *
     * Looks for the second parameter's type hint (the DTO class)
     *
     * @return class-string|null
     * @throws \ReflectionException
     */
    private function extractDtoClass(DtoHandlerInterface $handler): ?string
    {
        $reflection = new ReflectionClass($handler);

        // Check if __invoke() method exists
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
