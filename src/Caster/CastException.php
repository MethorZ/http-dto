<?php

declare(strict_types=1);

namespace MethorZ\Dto\Caster;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when type casting fails
 */
final class CastException extends RuntimeException
{
    public static function castFailed(string $type, mixed $value, ?Throwable $previous = null): self
    {
        $valueType = get_debug_type($value);
        $message = sprintf(
            'Failed to cast value of type "%s" to "%s"',
            $valueType,
            $type,
        );

        return new self($message, 0, $previous);
    }

    public static function invalidValue(string $type, mixed $value, string $reason): self
    {
        $valueType = get_debug_type($value);
        $message = sprintf(
            'Cannot cast value of type "%s" to "%s": %s',
            $valueType,
            $type,
            $reason,
        );

        return new self($message);
    }

    public static function unsupportedType(string $type): self
    {
        return new self(sprintf('Unsupported cast type: %s', $type));
    }
}
