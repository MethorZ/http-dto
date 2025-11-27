<?php

declare(strict_types=1);

namespace MethorZ\Dto\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use MethorZ\Dto\Exception\MappingException;
use MethorZ\Dto\Exception\ValidationException;
use MethorZ\Dto\RequestDtoMapperInterface;
use MethorZ\Dto\Response\JsonSerializableDto;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

use function count;

/**
 * PSR-15 wrapper for DtoHandlerInterface handlers
 *
 * This wrapper makes DTO handlers compatible with standard PSR-15 middleware
 * pipelines by:
 * 1. Automatically extracting the DTO class from the handler's __invoke() signature
 * 2. Mapping and validating the request to a DTO
 * 3. Calling the handler with the validated DTO
 * 4. Auto-serializing JsonSerializableDto responses to JSON
 * 5. Handling validation/mapping errors with custom error responses
 *
 * This eliminates the need for handlers to implement RequestHandlerInterface
 * themselves, making them cleaner and more focused on business logic.
 */
final readonly class DtoHandlerWrapper implements RequestHandlerInterface
{
    /**
     * @param DtoHandlerInterface $dtoHandler The DTO handler to wrap
     * @param RequestDtoMapperInterface $dtoMapper Maps requests to DTOs
     * @param callable(ValidationException|MappingException): ResponseInterface $errorHandler Converts exceptions to responses
     */
    public function __construct(
        private DtoHandlerInterface $dtoHandler,
        private RequestDtoMapperInterface $dtoMapper,
        private mixed $errorHandler,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            // Extract DTO class from handler's __invoke() signature
            $dtoClass = $this->extractDtoClass();

            if ($dtoClass === null) {
                // No DTO parameter found, call with null
                $response = $this->dtoHandler->__invoke($request, null);
            } else {
                // Map and validate request → DTO
                $dto = $this->dtoMapper->map($dtoClass, $request);

                // Call handler with validated DTO
                $response = $this->dtoHandler->__invoke($request, $dto);
            }

            // Auto-serialize JsonSerializableDto responses
            if ($response instanceof JsonSerializableDto) {
                return new JsonResponse(
                    $response->jsonSerialize(),
                    $response->getStatusCode(),
                );
            }

            return $response;
        } catch (ValidationException | MappingException $e) {
            // Convert exception to error response
            return ($this->errorHandler)($e);
        } catch (ReflectionException $e) {
            // Reflection failed - this shouldn't happen in production
            return ($this->errorHandler)(
                new MappingException(
                    'Failed to analyze handler signature: ' . $e->getMessage(),
                    0,
                    $e,
                ),
            );
        }
    }

    /**
     * Extract the DTO class from handler's __invoke() method signature
     *
     * Looks for the second parameter's type hint (the DTO class).
     * Falls back to @param annotation if no type hint present.
     *
     * @return class-string|null
     * @throws ReflectionException
     */
    private function extractDtoClass(): ?string
    {
        $reflection = new ReflectionClass($this->dtoHandler);

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

        // Try to get type from type hint first
        if ($dtoType instanceof ReflectionNamedType && ! $dtoType->isBuiltin()) {
            return $dtoType->getName();
        }

        // Fall back to @param annotation
        $docComment = $invokeMethod->getDocComment();
        if ($docComment !== false && preg_match('/@param\s+([^\s]+)\s+\$dto/', $docComment, $matches)) {
            $type = trim($matches[1]);

            // Already fully qualified?
            if (str_starts_with($type, '\\')) {
                return ltrim($type, '\\');
            }

            // Try to resolve from use statements
            $resolvedType = $this->resolveClassFromUseStatements($reflection, $type);
            if ($resolvedType !== null) {
                return $resolvedType;
            }

            // Assume same namespace as handler
            $namespace = $reflection->getNamespaceName();
            if ($namespace) {
                return $namespace . '\\' . $type;
            }

            return $type;
        }

        return null;
    }

    /**
     * Resolve a class name using the handler's use statements
     *
     * @param ReflectionClass $reflection
     * @param string $className
     * @return string|null
     */
    private function resolveClassFromUseStatements(ReflectionClass $reflection, string $className): ?string
    {
        $fileName = $reflection->getFileName();
        if ($fileName === false) {
            return null;
        }

        $fileContents = file_get_contents($fileName);
        if ($fileContents === false) {
            return null;
        }

        // Extract use statements
        if (preg_match_all('/^use\s+([^\s;]+)(?:\s+as\s+([^\s;]+))?\s*;/m', $fileContents, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fullClass = $match[1];
                $alias     = $match[2] ?? basename(str_replace('\\', '/', $fullClass));

                if ($alias === $className) {
                    return $fullClass;
                }
            }
        }

        return null;
    }
}

