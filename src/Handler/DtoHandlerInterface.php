<?php

declare(strict_types=1);

namespace MethorZ\Dto\Handler;

/**
 * Marker interface for handlers using automatic DTO injection
 *
 * Handlers implementing this interface must provide an __invoke() method with the signature:
 *   public function __invoke(ServerRequestInterface $request, YourDtoType $dto): JsonSerializableDto
 *
 * The DTO type is extracted via reflection from the actual type hint on the $dto parameter.
 *
 * Benefits:
 * - Full type safety with native PHP type hints
 * - Perfect IDE autocomplete and refactoring support
 * - Static analysis catches type errors at build time
 * - No PHPDoc parsing required
 * - Simpler, more maintainable code
 *
 * Example:
 * ```php
 * final readonly class CreateItemHandler implements DtoHandlerInterface
 * {
 *     public function __invoke(
 *         ServerRequestInterface $request,
 *         CreateItemRequest $dto  // Fully typed!
 *     ): JsonSerializableDto {
 *         return new ItemCreatedResponse($this->service->create($dto));
 *     }
 * }
 * ```
 *
 * Migration from v1.x:
 * Simply add the type hint to your $dto parameter. Remove PHPDoc @param if only used for type hinting.
 */
interface DtoHandlerInterface
{
    // Marker interface - implementations define their own __invoke() signature with typed DTO parameter
}
