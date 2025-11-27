<?php

declare(strict_types=1);

namespace MethorZ\Dto\Response;

use JsonSerializable;

/**
 * Interface for Response DTOs that can be automatically serialized to JSON
 *
 * Response DTOs implementing this interface will be automatically converted
 * to JsonResponse by the AutoJsonResponseMiddleware.
 *
 * Benefits:
 * - Handlers can return DTOs directly instead of ResponseInterface
 * - DTOs control their own HTTP status code
 * - Perfect type safety (handler returns specific DTO type)
 * - Automatic serialization (no manual ->toArray() calls)
 *
 * Example:
 * ```php
 * final readonly class ItemResponse implements JsonSerializableDto
 * {
 *     public function __construct(
 *         public string $id,
 *         public string $name,
 *     ) {}
 *
 *     public function jsonSerialize(): array
 *     {
 *         return ['id' => $this->id, 'name' => $this->name];
 *     }
 *
 *     public function getStatusCode(): int
 *     {
 *         return 201; // Created
 *     }
 * }
 * ```
 */
interface JsonSerializableDto extends JsonSerializable
{
    /**
     * Get the HTTP status code for this response
     *
     * Common status codes:
     * - 200: OK (successful GET, PUT)
     * - 201: Created (successful POST)
     * - 204: No Content (successful DELETE)
     *
     * @return int HTTP status code
     */
    public function getStatusCode(): int;
}
