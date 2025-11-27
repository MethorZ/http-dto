<?php

declare(strict_types=1);

namespace MethorZ\Dto\Caster;

use DateTimeImmutable;
use DateTimeInterface;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

use function is_string;

/**
 * Casts strings to DateTimeImmutable instances
 *
 * Supports:
 * - DateTimeImmutable
 * - DateTimeInterface (returns DateTimeImmutable)
 * - ISO 8601 format strings
 * - Timestamps
 */
final readonly class DateTimeCaster implements CasterInterface
{
    public function cast(mixed $value, ReflectionParameter $parameter): mixed
    {
        // Already a DateTime instance
        if ($value instanceof DateTimeInterface) {
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
                return new DateTimeImmutable($value);
            } catch (Throwable $e) {
                throw CastException::castFailed('DateTimeImmutable', $value, $e);
            }
        }

        // Cast from timestamp (int)
        if (is_int($value)) {
            return (new DateTimeImmutable())->setTimestamp($value);
        }

        throw CastException::invalidValue(
            'DateTimeImmutable',
            $value,
            'Expected string (ISO 8601) or timestamp',
        );
    }

    public function supports(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType) {
            return false;
        }

        $typeName = $type->getName();

        return $typeName === DateTimeImmutable::class
            || $typeName === DateTimeInterface::class;
    }
}
