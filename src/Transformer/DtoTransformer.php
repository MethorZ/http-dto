<?php

declare(strict_types=1);

namespace MethorZ\Dto\Transformer;

use function array_map;
use function explode;
use function implode;
use function preg_replace_callback;
use function strtolower;

/**
 * Transforms DTO data between different formats
 *
 * Supports:
 * - snake_case ↔ camelCase
 * - Nested array filtering
 * - Custom key mapping
 * - Data masking
 *
 * Usage:
 * ```php
 * $transformer = new DtoTransformer();
 *
 * // Convert to snake_case
 * $snakeCase = $transformer->toSnakeCase($array);
 *
 * // Convert to camelCase
 * $camelCase = $transformer->toCamelCase($array);
 *
 * // Filter specific keys
 * $filtered = $transformer->filterKeys($array, ['id', 'name']);
 * ```
 */
final readonly class DtoTransformer
{
    /**
     * Transform array keys from camelCase to snake_case
     *
     * Recursively transforms nested arrays.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function toSnakeCase(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $snakeKey = $this->camelToSnake($key);

            if (is_array($value)) {
                $result[$snakeKey] = $this->toSnakeCase($value);
            } else {
                $result[$snakeKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Transform array keys from snake_case to camelCase
     *
     * Recursively transforms nested arrays.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function toCamelCase(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $camelKey = $this->snakeToCamel($key);

            if (is_array($value)) {
                $result[$camelKey] = $this->toCamelCase($value);
            } else {
                $result[$camelKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Filter array to only include specified keys
     *
     * @param array<string, mixed> $data
     * @param array<string> $allowedKeys
     * @return array<string, mixed>
     */
    public function filterKeys(array $data, array $allowedKeys): array
    {
        $result = [];

        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $data)) {
                $result[$key] = $data[$key];
            }
        }

        return $result;
    }

    /**
     * Exclude specific keys from array
     *
     * @param array<string, mixed> $data
     * @param array<string> $excludedKeys
     * @return array<string, mixed>
     */
    public function excludeKeys(array $data, array $excludedKeys): array
    {
        $result = $data;

        foreach ($excludedKeys as $key) {
            unset($result[$key]);
        }

        return $result;
    }

    /**
     * Rename keys in array
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $keyMap Map of old_key => new_key
     * @return array<string, mixed>
     */
    public function renameKeys(array $data, array $keyMap): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $newKey = $keyMap[$key] ?? $key;
            $result[$newKey] = $value;
        }

        return $result;
    }

    /**
     * Mask sensitive values in array
     *
     * @param array<string, mixed> $data
     * @param array<string> $sensitiveKeys
     * @return array<string, mixed>
     */
    public function maskSensitiveData(array $data, array $sensitiveKeys, string $mask = '***'): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $sensitiveKeys, true)) {
                $result[$key] = $mask;
            } elseif (is_array($value)) {
                $result[$key] = $this->maskSensitiveData($value, $sensitiveKeys, $mask);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Flatten nested array with dot notation keys
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function flatten(array $data, string $prefix = ''): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $newKey = $prefix === '' ? $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $result = [...$result, ...$this->flatten($value, $newKey)];
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Unflatten array with dot notation keys
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function unflatten(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $keys = explode('.', $key);
            $current = &$result;

            foreach ($keys as $i => $k) {
                if ($i === count($keys) - 1) {
                    $current[$k] = $value;
                } else {
                    if (!isset($current[$k]) || !is_array($current[$k])) {
                        $current[$k] = [];
                    }

                    $current = &$current[$k];
                }
            }
        }

        return $result;
    }

    /**
     * Convert camelCase string to snake_case
     */
    private function camelToSnake(string $input): string
    {
        $result = preg_replace_callback(
            '/([A-Z])/',
            fn ($matches) => '_' . strtolower($matches[1]),
            $input,
        );

        return ltrim($result ?? $input, '_');
    }

    /**
     * Convert snake_case string to camelCase
     */
    private function snakeToCamel(string $input): string
    {
        $words = explode('_', $input);
        $first = array_shift($words);

        if ($first === null) {
            return '';
        }

        $rest = array_map('ucfirst', $words);

        return $first . implode('', $rest);
    }
}
