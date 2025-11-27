<?php

declare(strict_types=1);

namespace MethorZ\Dto\Handler;

use MethorZ\Dto\Response\JsonSerializableDto;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Marker interface for handlers that use automatic DTO parameter injection
 *
 * Handlers implementing this interface can use the __invoke() method with
 * a DTO parameter that will be automatically mapped, validated, and injected
 * by the AutoDtoInjectionMiddleware.
 *
 * This interface does NOT extend RequestHandlerInterface because:
 * - The middleware intercepts and calls __invoke() directly
 * - No PSR-15 handle() method is needed (eliminates boilerplate)
 * - Cleaner separation between DTO pattern and standard PSR-15 pattern
 *
 * Benefits:
 * - Type-safe DTO parameter injection
 * - Automatic request mapping and validation
 * - Clean handler signatures (no obsolete handle() method)
 * - Compile-time type checking
 *
 * Example:
 * ```php
 * final readonly class CreateItemHandler implements DtoHandlerInterface
 * {
 *     public function __invoke(
 *         ServerRequestInterface $request,
 *         CreateItemRequest $dto  // ← Automatically injected!
 *     ): ItemResponse {
 *         return $this->service->execute($dto);
 *     }
 * }
 * ```
 */
interface DtoHandlerInterface
{
    /**
     * Handle the request with automatic DTO injection
     *
     * The second parameter will be automatically:
     * - Mapped from the request body/query/route parameters
     * - Validated using Symfony Validator attributes
     * - Injected as a typed parameter
     *
     * The return value must be a DTO implementing JsonSerializableDto,
     * which will be automatically serialized to JSON with the appropriate
     * HTTP status code.
     *
     * Note: The $dto parameter has no type hint in the interface to allow
     * implementations to specify their own specific DTO types (e.g., CreateItemRequest).
     * This is required for PHP's contravariance rules.
     *
     * @param ServerRequestInterface $request The PSR-7 request
     * @param object $dto The validated DTO (specific type determined by implementation)
     * @return JsonSerializableDto Response DTO (automatically serialized)
     *
     * @phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function __invoke(ServerRequestInterface $request, $dto): JsonSerializableDto;
}
