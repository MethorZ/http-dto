<?php

declare(strict_types=1);

namespace MethorZ\Dto\Caster;

use ReflectionNamedType;
use ReflectionParameter;

/**
 * Casts scalar types: string, int, float, bool, array
 *
 * This is the default caster that handles basic PHP scalar types.
 */
final readonly class ScalarCaster implements CasterInterface
{
    public function cast(mixed $value, ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        // No type hint or union/intersection type - return as-is
        if ($type === null || ! $type instanceof ReflectionNamedType) {
            return $value;
        }

        $typeName = $type->getName();

        // Null handling
        if ($value === null && $type->allowsNull()) {
            return null;
        }

        // Cast to appropriate scalar type
        return match ($typeName) {
            'string' => is_scalar($value) || $value === null ? (string) $value : $value,
            'int' => is_numeric($value) || $value === null ? (int) $value : $value,
            'float' => is_numeric($value) || $value === null ? (float) $value : $value,
            'bool' => (bool) $value,
            // 'array' removed - handled by CollectionCaster
            'mixed' => $value,
            default => $value, // Non-scalar type, pass through
        };
    }

    public function supports(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType) {
            return false;
        }

        $typeName = $type->getName();

        // Note: 'array' removed - arrays should be handled by CollectionCaster
        return in_array($typeName, ['string', 'int', 'float', 'bool', 'mixed'], true);
    }
}
