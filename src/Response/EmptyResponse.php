<?php

declare(strict_types=1);

namespace MethorZ\Dto\Response;

/**
 * Empty Response DTO for DELETE operations
 *
 * Returns HTTP 204 No Content with an empty response body.
 * Commonly used for successful DELETE operations where no response data is needed.
 *
 * Usage:
 * ```php
 * public function __invoke(ServerRequestInterface $request, DeleteRequest $dto): JsonSerializableDto
 * {
 *     $this->service->delete($dto->id);
 *     return new EmptyResponse();
 * }
 * ```
 */
final readonly class EmptyResponse implements JsonSerializableDto
{
    /**
     * @return array<never, never>
     */
    public function jsonSerialize(): array
    {
        return [];
    }

    public function getStatusCode(): int
    {
        return 204; // No Content
    }
}
