<?php

declare(strict_types=1);

namespace MethorZ\Dto\Caster;

use BackedEnum;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;
use UnitEnum;

use function is_int;
use function is_string;

/**
 * Casts values to PHP 8.1+ backed enums
 *
 * Supports:
 * - Backed enums (string or int)
 * - Enum instances (pass through)
 */
final readonly class EnumCaster implements CasterInterface
{
    public function cast(mixed $value, ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType) {
            throw CastException::unsupportedType('Non-named enum type');
        }

        $enumClass = $type->getName();

        // Already an enum instance
        if ($value instanceof $enumClass) {
            return $value;
        }

        // Null handling
        if ($value === null && $type->allowsNull()) {
            return null;
        }

        // Verify enum exists
        if (! enum_exists($enumClass)) {
            throw CastException::unsupportedType($enumClass);
        }

        try {
            /** @var class-string<UnitEnum> $enumClass */
            $reflection = new ReflectionEnum($enumClass);

            // Only backed enums are supported for casting
            if (! $reflection->isBacked()) {
                throw CastException::invalidValue(
                    $enumClass,
                    $value,
                    'Only backed enums can be cast from values',
                );
            }

            // Cast from int or string
            if (is_int($value) || is_string($value)) {
                /** @var BackedEnum $enumClass */
                return $enumClass::from($value);
            }

            throw CastException::invalidValue(
                $enumClass,
                $value,
                'Expected string or int for backed enum',
            );
        } catch (Throwable $e) {
            $typeName = is_string($enumClass) ? $enumClass : get_class($enumClass);
            throw CastException::castFailed($typeName, $value, $e);
        }
    }

    public function supports(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType) {
            return false;
        }

        $typeName = $type->getName();

        // Check if the type is an enum
        if (! enum_exists($typeName)) {
            return false;
        }

        // Check if it's a backed enum
        try {
            $reflection = new ReflectionEnum($typeName);
            return $reflection->isBacked();
        } catch (Throwable) {
            return false;
        }
    }
}
