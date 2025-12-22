<?php

declare(strict_types=1);

namespace MethorZ\Dto\Middleware;

use MethorZ\Dto\Exception\MappingException;
use MethorZ\Dto\Exception\ValidationException;
use MethorZ\Dto\Handler\DtoHandlerInterface;
use MethorZ\Dto\RequestDtoMapperInterface;
use MethorZ\Dto\Response\JsonResponseFactory;
use MethorZ\Dto\Response\JsonSerializableDto;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

use function count;
use function sprintf;

/**
 * Automatic DTO injection middleware
 *
 * This middleware automatically handles DTO mapping, validation, and injection:
 * 1. Detects handlers implementing DtoHandlerInterface
 * 2. Inspects __invoke() signature to determine DTO class from type hint
 * 3. Maps request to DTO
 * 4. Validates DTO
 * 5. Catches validation/mapping exceptions and converts to error responses
 * 6. Injects validated DTO as parameter to handler's __invoke()
 * 7. Auto-serializes JsonSerializableDto responses to JSON
 *
 * Usage: Just register this one middleware in your pipeline!
 */
final readonly class AutoDtoInjectionMiddleware implements MiddlewareInterface
{
    /**
     * @param RequestDtoMapperInterface $dtoMapper Maps requests to DTOs
     * @param JsonResponseFactory $jsonResponseFactory Creates JSON responses
     * @param callable(ValidationException|MappingException): ResponseInterface $errorHandler
     */
    public function __construct(
        private RequestDtoMapperInterface $dtoMapper,
        private JsonResponseFactory $jsonResponseFactory,
        private mixed $errorHandler,
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        // Only process handlers that implement DtoHandlerInterface
        if (!$handler instanceof DtoHandlerInterface) {
            return $handler->handle($request);
        }

        try {
            // Get the DTO class from the handler's __invoke() type hint
            $dtoClass = $this->extractDtoClass($handler);

            // Map and validate request → DTO
            $dto = $this->dtoMapper->map($dtoClass, $request);

            // Call handler with DTO injected as parameter (use callable invocation)
            /** @var callable(ServerRequestInterface, object): JsonSerializableDto $handler */
            $response = $handler($request, $dto);

            // Auto-serialize JsonSerializableDto responses
            return $this->jsonResponseFactory->fromDto($response);
        } catch (ValidationException | MappingException $e) {
            // Convert exception to error response
            return ($this->errorHandler)($e);
        } catch (ReflectionException $e) {
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
     * Requires the second parameter to have a class type hint.
     *
     * @return class-string
     * @throws MappingException If handler is improperly configured
     * @throws ReflectionException
     */
    private function extractDtoClass(DtoHandlerInterface $handler): string
    {
        $reflection = new ReflectionClass($handler);

        // Check if __invoke() method exists
        if (!$reflection->hasMethod('__invoke')) {
            throw new MappingException(
                sprintf(
                    'DtoHandlerInterface implementation %s must provide an __invoke() method',
                    $handler::class,
                ),
            );
        }

        $invokeMethod = $reflection->getMethod('__invoke');
        $parameters = $invokeMethod->getParameters();

        // __invoke() must have at least 2 parameters
        if (count($parameters) < 2) {
            throw new MappingException(
                sprintf(
                    '%s::__invoke() must have at least 2 parameters: (ServerRequestInterface $request, YourDtoType $dto)',
                    $handler::class,
                ),
            );
        }

        // Get the second parameter (the DTO)
        $dtoParameter = $parameters[1];
        $dtoType = $dtoParameter->getType();

        // Require type hint
        if (!$dtoType instanceof ReflectionNamedType || $dtoType->isBuiltin()) {
            $typeName = $dtoType instanceof ReflectionNamedType ? $dtoType->getName() : 'no type';

            throw new MappingException(
                sprintf(
                    'The $dto parameter in %s::__invoke() must have a class type hint. Found: %s',
                    $handler::class,
                    $typeName,
                ),
            );
        }

        /** @var class-string */
        return $dtoType->getName();
    }
}
