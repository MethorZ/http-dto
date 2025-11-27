<?php

declare(strict_types=1);

namespace MethorZ\Dto\Response;

use MethorZ\Dto\Trait\DtoArrayConversionTrait;

/**
 * Base class for Response DTOs with automatic array conversion
 *
 * Provides:
 * - Automatic toArray() implementation via trait
 * - Automatic fromArray() factory method
 * - JsonSerializable implementation
 * - Default 200 OK status code (can be overridden)
 *
 * Usage:
 * ```php
 * final readonly class UserResponse extends AbstractDto
 * {
 *     public function __construct(
 *         public string $id,
 *         public string $name,
 *     ) {}
 *
 *     public function getStatusCode(): int
 *     {
 *         return 201; // Override for created resources
 *     }
 * }
 *
 * // Convert to array (automatic)
 * $array = $userResponse->toArray();
 *
 * // Create from array (automatic)
 * $user = UserResponse::fromArray(['id' => '1', 'name' => 'John']);
 *
 * // JSON serialization (automatic)
 * json_encode($userResponse);
 * ```
 */
abstract readonly class AbstractDto implements JsonSerializableDto
{
    use DtoArrayConversionTrait;

    /**
     * Default HTTP status code for responses
     *
     * Override in subclasses to return different status codes:
     * - 200: OK (default)
     * - 201: Created
     * - 204: No Content
     */
    public function getStatusCode(): int
    {
        return 200;
    }
}
