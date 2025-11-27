<?php

declare(strict_types=1);

namespace MethorZ\Dto\Exception;

use RuntimeException;
use Throwable;

use function sprintf;

final class MappingException extends RuntimeException
{
    public static function classNotFound(string $className, Throwable $previous): self
    {
        return new self(
            sprintf('DTO class "%s" not found or cannot be reflected', $className),
            0,
            $previous,
        );
    }

    public static function noConstructor(string $className): self
    {
        return new self(
            sprintf('DTO class "%s" has no constructor', $className),
        );
    }

    public static function instantiationFailed(string $className, Throwable $previous): self
    {
        return new self(
            sprintf('Failed to instantiate DTO "%s": %s', $className, $previous->getMessage()),
            0,
            $previous,
        );
    }

    public static function missingRequiredParameter(string $className, string $parameterName): self
    {
        return new self(
            sprintf(
                'Required parameter "%s" for DTO "%s" is missing from request',
                $parameterName,
                $className,
            ),
        );
    }
}
