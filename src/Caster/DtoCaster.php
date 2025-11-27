<?php

declare(strict_types=1);

namespace MethorZ\Dto\Caster;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

use function is_array;

/**
 * Casts arrays to nested DTO instances
 *
 * Enables nested DTO support by recursively mapping arrays to DTO constructors.
 *
 * Example:
 * ```php
 * final readonly class CreateOrderRequest
 * {
 *     public function __construct(
 *         public Address $shippingAddress,  // ← Nested DTO
 *     ) {}
 * }
 * ```
 */
final readonly class DtoCaster implements CasterInterface
{
    public function __construct(
        private ?CasterRegistry $registry = null,
    ) {
    }

    public function cast(mixed $value, ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType) {
            throw CastException::unsupportedType('Non-named DTO type');
        }

        $dtoClass = $type->getName();

        // Already a DTO instance
        if ($value instanceof $dtoClass) {
            return $value;
        }

        // Null handling
        if ($value === null && $type->allowsNull()) {
            return null;
        }

        // Cast from array
        if (is_array($value)) {
            try {
                return $this->instantiateDto($dtoClass, $value);
            } catch (Throwable $e) {
                throw CastException::castFailed($dtoClass, $value, $e);
            }
        }

        throw CastException::invalidValue(
            $dtoClass,
            $value,
            'Expected array for nested DTO',
        );
    }

    public function supports(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType) {
            return false;
        }

        $typeName = $type->getName();

        // Check if it's a class (not built-in type)
        if (! class_exists($typeName)) {
            return false;
        }

        // Check if it has a constructor (DTOs should have constructors)
        try {
            $reflection = new ReflectionClass($typeName);
            return $reflection->getConstructor() !== null;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Instantiate nested DTO from array data
     *
     * @param class-string $dtoClass
     * @param array<string, mixed> $data
     * @throws CastException
     * @throws \ReflectionException
     */
    public function instantiateDto(string $dtoClass, array $data): object
    {
        $reflection = new ReflectionClass($dtoClass);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            throw CastException::invalidValue(
                $dtoClass,
                $data,
                'DTO must have a constructor',
            );
        }

        $args = [];

        foreach ($constructor->getParameters() as $param) {
            $paramName = $param->getName();

            // Get value from array
            if (! isset($data[$paramName])) {
                if ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                    continue;
                }

                throw CastException::invalidValue(
                    $dtoClass,
                    $data,
                    sprintf('Missing required parameter: %s', $paramName),
                );
            }

            // Recursively cast nested parameters
            $args[] = $this->castParameter($data[$paramName], $param);
        }

        return $reflection->newInstanceArgs($args);
    }

    /**
     * Cast parameter value using the registry if available
     *
     * @throws CastException
     */
    private function castParameter(mixed $value, ReflectionParameter $param): mixed
    {
        // If we have a registry, use it to find the appropriate caster
        if ($this->registry !== null) {
            $caster = $this->registry->resolve($param);

            // Avoid infinite recursion - don't use DtoCaster for parameters
            if ($caster !== null && ! $caster instanceof self) {
                return $caster->cast($value, $param);
            }
        }


        // Fallback to basic type casting
        $type = $param->getType();

        if (! $type instanceof ReflectionNamedType) {
            return $value;
        }

        $typeName = $type->getName();

        // Handle nested DTOs recursively
        if (class_exists($typeName) && is_array($value)) {
            /** @var class-string $typeName */
            /** @var array<string, mixed> $value */
            return $this->instantiateDto($typeName, $value);
        }

        // Basic scalar casting
        return match ($typeName) {
            'string' => is_scalar($value) || $value === null ? (string) $value : $value,
            'int' => is_numeric($value) || $value === null ? (int) $value : $value,
            'float' => is_numeric($value) || $value === null ? (float) $value : $value,
            'bool' => (bool) $value,
            'array' => is_array($value) ? $value : (array) $value,
            default => $value,
        };
    }
}
