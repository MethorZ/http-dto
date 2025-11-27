<?php

declare(strict_types=1);

namespace MethorZ\Dto\Caster;

use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

use function is_array;
use function preg_match;

/**
 * Casts arrays of objects using PHPDoc type hints
 *
 * Supports collection notation in PHPDoc:
 * - `@var array<Item>`
 * - `@var array<int, Item>`
 * - `@var Item[]`
 *
 * Example:
 * ```php
 * public function __construct(
 *     /** @var array<OrderItem> *\/
 *     public array $items,
 * ) {}
 * ```
 */
final readonly class CollectionCaster implements CasterInterface
{
    public function __construct(
        private ?CasterRegistry $registry = null,
        private ?DtoCaster $dtoCaster = null,
    ) {
    }

    public function cast(mixed $value, ReflectionParameter $parameter): mixed
    {
        // Must be an array
        if (! is_array($value)) {
            throw CastException::invalidValue(
                'array',
                $value,
                'Expected array for collection',
            );
        }

        // Null handling (empty array)
        $type = $parameter->getType();
        if (empty($value) && $type instanceof ReflectionNamedType && $type->allowsNull()) {
            return [];
        }

        // Extract item type from PHPDoc
        $itemType = $this->extractItemType($parameter);

        if ($itemType === null) {
            // No type information, return as-is
            return $value;
        }

        // Cast each item in the collection
        try {
            return array_map(
                fn (mixed $item): mixed => $this->castItem($item, $itemType),
                $value,
            );
        } catch (Throwable $e) {
            throw CastException::castFailed("array<{$itemType}>", $value, $e);
        }
    }

    public function supports(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();

        // Must be array type
        if (! $type instanceof ReflectionNamedType || $type->getName() !== 'array') {
            return false;
        }

        // Must have PHPDoc with collection type
        return $this->extractItemType($parameter) !== null;
    }

    /**
     * Extract item type from PHPDoc annotation
     *
     * Supports:
     * - `@param array<ItemType> $name`
     * - `@param array<int, ItemType> $name`
     * - `@param ItemType[] $name`
     * - `@var array<ItemType>`
     * - `@var ItemType[]`
     */
    private function extractItemType(ReflectionParameter $parameter): ?string
    {
        $function = $parameter->getDeclaringFunction();
        $docComment = $function->getDocComment();
        if ($docComment === false) {
            return null;
        }

        $paramName = $parameter->getName();

        // Pattern 1: @param array<int|string|mixed, Type> $paramName
        // Matches namespace paths like \Vendor\Package\Class or Vendor\Package\Class
        $pattern1 = '/@param\s+array<(?:int|string|mixed),\s*\\\\?([a-zA-Z_][a-zA-Z0-9_\\\\]*)>\s+\$'
            . preg_quote($paramName, '/') . '/';
        if (preg_match($pattern1, $docComment, $matches) === 1) {
            return ltrim($matches[1], '\\');
        }

        // Pattern 2: @param array<Type> $paramName
        $pattern2 = '/@param\s+array<\\\\?([a-zA-Z_][a-zA-Z0-9_\\\\]*)>\s+\$' . preg_quote($paramName, '/') . '/';
        if (preg_match($pattern2, $docComment, $matches) === 1) {
            return ltrim($matches[1], '\\');
        }

        // Pattern 3: @param Type[] $paramName
        $pattern3 = '/@param\s+\\\\?([a-zA-Z_][a-zA-Z0-9_\\\\]*)\[\]\s+\$' . preg_quote($paramName, '/') . '/';
        if (preg_match($pattern3, $docComment, $matches) === 1) {
            return ltrim($matches[1], '\\');
        }

        // Fallback: Try @var patterns (for properties or contexts without @param)
        // Pattern 4: @var array<int|string|mixed, Type>
        $pattern4 = '/@var\s+array<(?:int|string|mixed),\s*\\\\?([a-zA-Z_][a-zA-Z0-9_\\\\]*)>/';
        if (preg_match($pattern4, $docComment, $matches) === 1) {
            return ltrim($matches[1], '\\');
        }

        // Pattern 5: @var array<Type>
        $pattern5 = '/@var\s+array<\\\\?([a-zA-Z_][a-zA-Z0-9_\\\\]*)>/';
        if (preg_match($pattern5, $docComment, $matches) === 1) {
            return ltrim($matches[1], '\\');
        }

        // Pattern 6: @var Type[]
        $pattern6 = '/@var\s+\\\\?([a-zA-Z_][a-zA-Z0-9_\\\\]*)\[\]/';
        if (preg_match($pattern6, $docComment, $matches) === 1) {
            return ltrim($matches[1], '\\');
        }

        return null;
    }

    /**
     * Cast a single item in the collection
     *
     * @throws CastException
     */
    private function castItem(mixed $item, string $itemType): mixed
    {
        // If item is already the correct type, return as-is
        if (is_object($item) && $item instanceof $itemType) {
            return $item;
        }

        // If item type is a class and item is an array, use DtoCaster for proper nested handling
        if (class_exists($itemType) && is_array($item)) {
            /** @var array<string, mixed> $item */

            // Use DtoCaster directly if available for full pipeline support
            if ($this->dtoCaster !== null) {
                return $this->dtoCaster->instantiateDto($itemType, $item);
            }

            // Fallback to basic instantiation
            return $this->castToDto($item, $itemType);
        }

        // Handle Enums
        if (enum_exists($itemType)) {
            return EnumCaster::castToEnum($item, $itemType);
        }

        // Scalar types - basic casting
        return match ($itemType) {
            'string' => is_scalar($item) || $item === null ? (string) $item : $item,
            'int' => is_numeric($item) || $item === null ? (int) $item : $item,
            'float' => is_numeric($item) || $item === null ? (float) $item : $item,
            'bool' => (bool) $item,
            default => $item,
        };
    }

    /**
     * Cast array to DTO using DtoCaster logic
     *
     * @param array<string, mixed> $data
     * @param class-string $dtoClass
     * @throws CastException
     * @throws \ReflectionException
     */
    private function castToDto(array $data, string $dtoClass): object
    {
        $reflection = new \ReflectionClass($dtoClass);
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

            $args[] = $this->castParameterValue($data[$paramName], $param);
        }

        return $reflection->newInstanceArgs($args);
    }

    /**
     * Cast parameter value - uses registry if available for proper nesting
     *
     * @throws CastException
     * @phpcs:disable Generic.Metrics.CyclomaticComplexity.MaxExceeded
     */
    private function castParameterValue(mixed $value, \ReflectionParameter $param): mixed
    {
        // Use registry for proper casting if available
        if ($this->registry !== null) {
            $caster = $this->registry->resolve($param);
            if ($caster !== null && ! $caster instanceof self) {
                return $caster->cast($value, $param);
            }
        }

        // Fallback to basic handling
        $type = $param->getType();

        if (! $type instanceof ReflectionNamedType) {
            return $value;
        }

        $typeName = $type->getName();

        // Handle nested DTOs (non-array)
        if (class_exists($typeName) && is_array($value)) {
            return $this->castToDto($value, $typeName);
        }

        // Handle array parameters - check if it's a typed collection
        if ($typeName === 'array' && is_array($value)) {
            $itemType = $this->extractItemType($param);

            // If we have type information and it's a class, cast each item
            if ($itemType !== null && class_exists($itemType)) {
                return array_map(
                    fn (mixed $item): mixed => is_array($item)
                        ? $this->castToDto($item, $itemType)
                        : $item,
                    $value,
                );
            }

            return $value;
        }

        // Basic scalar casting
        return match ($typeName) {
            'string' => is_scalar($value) || $value === null ? (string) $value : $value,
            'int' => is_numeric($value) || $value === null ? (int) $value : $value,
            'float' => is_numeric($value) || $value === null ? (float) $value : $value,
            'bool' => (bool) $value,
            default => $value,
        };
    }
}
