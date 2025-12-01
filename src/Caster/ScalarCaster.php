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
        return $this->castToScalarType($value, $typeName);
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

    /**
     * Cast value to the specified scalar type
     */
    private function castToScalarType(mixed $value, string $typeName): mixed
    {
        return match ($typeName) {
            'string' => $this->toScalarString($value),
            'int' => $this->toInt($value),
            'float' => $this->toFloat($value),
            'bool' => (bool) $value,
            'mixed' => $value,
            default => $value,
        };
    }

    /**
     * Cast value to string if possible
     */
    private function toScalarString(mixed $value): mixed
    {
        return is_scalar($value) || $value === null ? (string) $value : $value;
    }

    /**
     * Cast value to int if possible
     */
    private function toInt(mixed $value): mixed
    {
        return is_numeric($value) || $value === null ? (int) $value : $value;
    }

    /**
     * Cast value to float if possible
     */
    private function toFloat(mixed $value): mixed
    {
        return is_numeric($value) || $value === null ? (float) $value : $value;
    }
}
