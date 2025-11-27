<?php

declare(strict_types=1);

namespace MethorZ\Dto\Trait;

use ReflectionClass;
use ReflectionProperty;

/**
 * Provides automatic array conversion for DTOs
 *
 * This trait adds toArray() and fromArray() methods to any DTO class,
 * automatically mapping all public properties without manual implementation.
 *
 * Usage:
 * ```php
 * final readonly class UserDto
 * {
 *     use DtoArrayConversionTrait;
 *
 *     public function __construct(
 *         public string $id,
 *         public string $name,
 *         public string $email,
 *     ) {}
 * }
 *
 * // Convert to array
 * $array = $dto->toArray();
 *
 * // Create from array
 * $dto = UserDto::fromArray(['id' => '1', 'name' => 'John', 'email' => 'john@example.com']);
 * ```
 */
trait DtoArrayConversionTrait
{
    /**
     * Convert DTO to array
     *
     * Automatically includes all public properties.
     * Recursively converts nested DTOs that also implement toArray().
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        $data = [];

        foreach ($properties as $property) {
            $value = $property->getValue($this);
            $name = $property->getName();

            // Handle nested DTOs
            if (is_object($value) && method_exists($value, 'toArray')) {
                $data[$name] = $value->toArray();
            } elseif (is_array($value)) {
                // Handle arrays of DTOs
                $data[$name] = array_map(
                    fn ($item) => is_object($item) && method_exists($item, 'toArray')
                        ? $item->toArray()
                        : $item,
                    $value,
                );
            } elseif ($value instanceof \DateTimeInterface) {
                // Serialize DateTime objects to ISO 8601
                $data[$name] = $value->format('c');
            } elseif ($value instanceof \BackedEnum) {
                // Serialize enums to their backing value
                $data[$name] = $value->value;
            } elseif ($value instanceof \UnitEnum) {
                // Serialize unit enums to their name
                $data[$name] = $value->name;
            } else {
                $data[$name] = $value;
            }
        }

        return $data;
    }

    /**
     * Create DTO from array
     *
     * Uses the constructor to instantiate the DTO.
     * Assumes all constructor parameters match array keys.
     *
     * @param array<string, mixed> $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        $reflection = new ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new static();
        }

        $args = [];

        foreach ($constructor->getParameters() as $parameter) {
            $paramName = $parameter->getName();

            if (array_key_exists($paramName, $data)) {
                $args[] = $data[$paramName];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
            } else {
                throw new \InvalidArgumentException(
                    sprintf('Missing required parameter "%s" for %s', $paramName, static::class),
                );
            }
        }

        return new static(...$args);
    }

    /**
     * Implement jsonSerialize() for JsonSerializable interface
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
