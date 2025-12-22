<?php

declare(strict_types=1);

namespace MethorZ\Dto\Handler;

use MethorZ\Dto\Exception\MappingException;
use MethorZ\Dto\Exception\ValidationException;
use MethorZ\Dto\RequestDtoMapperInterface;
use MethorZ\Dto\Response\JsonResponseFactory;
use MethorZ\Dto\Response\JsonSerializableDto;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

use function count;
use function sprintf;

/**
 * PSR-15 wrapper for DtoHandlerInterface handlers
 *
 * This wrapper makes DTO handlers compatible with standard PSR-15 middleware
 * pipelines by:
 * 1. Automatically extracting the DTO class from the handler's __invoke() type hint
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
     * @param JsonResponseFactory $jsonResponseFactory Creates JSON responses
     * @param callable(ValidationException|MappingException): ResponseInterface $errorHandler
     *        Converts exceptions to responses
     */
    public function __construct(
        private DtoHandlerInterface $dtoHandler,
        private RequestDtoMapperInterface $dtoMapper,
        private JsonResponseFactory $jsonResponseFactory,
        private mixed $errorHandler,
    ) {
    }

    /**
     * Handle the request by extracting DTO, validating, and calling the handler
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            // Extract DTO class from handler's __invoke() type hint
            $dtoClass = $this->extractDtoClass();

            // Map and validate request → DTO
            $dto = $this->dtoMapper->map($dtoClass, $request);

            // Call handler with validated DTO (use callable invocation)
            /** @var callable(ServerRequestInterface, object): JsonSerializableDto $handler */
            $handler = $this->dtoHandler;
            $response = $handler($request, $dto);

            // Auto-serialize JsonSerializableDto responses
            return $this->jsonResponseFactory->fromDto($response);
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
     * Extract the DTO class from handler's __invoke() method type hint
     *
     * Requires the second parameter to have a type hint (the DTO class).
     *
     * @return class-string
     * @throws MappingException If __invoke() method is missing or improperly typed
     * @throws ReflectionException
     */
    private function extractDtoClass(): string
    {
        $reflection = new ReflectionClass($this->dtoHandler);

        // Check if __invoke() method exists
        if (!$reflection->hasMethod('__invoke')) {
            throw new MappingException(
                sprintf(
                    'DtoHandlerInterface implementation %s must provide an __invoke() method',
                    $this->dtoHandler::class,
                ),
            );
        }

        $invokeMethod = $reflection->getMethod('__invoke');
        $parameters = $invokeMethod->getParameters();

        // __invoke() must have at least 2 parameters: (ServerRequestInterface $request, DtoClass $dto)
        if (count($parameters) < 2) {
            throw new MappingException(
                sprintf(
                    '%s::__invoke() must have at least 2 parameters: (ServerRequestInterface $request, YourDtoType $dto)',
                    $this->dtoHandler::class,
                ),
            );
        }

        // Get the second parameter (the DTO)
        $dtoParameter = $parameters[1];
        $dtoType = $dtoParameter->getType();

        // Require type hint (no fallback to PHPDoc)
        if (!$dtoType instanceof ReflectionNamedType || $dtoType->isBuiltin()) {
            $typeName = $dtoType instanceof ReflectionNamedType ? $dtoType->getName() : 'no type';

            throw new MappingException(
                sprintf(
                    'The $dto parameter in %s::__invoke() must have a class type hint. Found: %s',
                    $this->dtoHandler::class,
                    $typeName,
                ),
            );
        }

        /** @var class-string */
        return $dtoType->getName();
    }
}
