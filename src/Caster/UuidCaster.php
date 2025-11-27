<?php

declare(strict_types=1);

namespace MethorZ\Dto\Caster;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

use function is_string;

/**
 * Casts strings to Ramsey UUID instances
 *
 * Supports:
 * - Ramsey\Uuid\UuidInterface
 * - Ramsey\Uuid\Uuid
 * - UUID string format (e.g., "550e8400-e29b-41d4-a716-446655440000")
 */
final readonly class UuidCaster implements CasterInterface
{
    public function cast(mixed $value, ReflectionParameter $parameter): mixed
    {
        // Already a UUID instance
        if ($value instanceof UuidInterface) {
            return $value;
        }

        // Null handling
        $type = $parameter->getType();
        if ($value === null && $type instanceof ReflectionNamedType && $type->allowsNull()) {
            return null;
        }

        // Cast from string
        if (is_string($value)) {
            try {
                return Uuid::fromString($value);
            } catch (Throwable $e) {
                throw CastException::castFailed('UuidInterface', $value, $e);
            }
        }

        throw CastException::invalidValue(
            'UuidInterface',
            $value,
            'Expected valid UUID string',
        );
    }

    public function supports(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType) {
            return false;
        }

        $typeName = $type->getName();

        return $typeName === UuidInterface::class
            || $typeName === Uuid::class;
    }
}
