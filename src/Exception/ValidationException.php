<?php

declare(strict_types=1);

namespace MethorZ\Dto\Exception;

use RuntimeException;

/**
 * Framework-agnostic validation exception
 *
 * Thrown when DTO validation fails. Contains an array of validation errors
 * where keys are property paths and values are error messages.
 */
final class ValidationException extends RuntimeException
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(
        string $message,
        private readonly array $errors,
    ) {
        parent::__construct($message);
    }

    /**
     * Create validation exception from errors array
     *
     * @param array<string, string> $errors Map of property path => error message
     */
    public static function fromErrors(array $errors, string $message = 'DTO validation failed'): self
    {
        return new self($message, $errors);
    }

    /**
     * Get validation errors
     *
     * @return array<string, string> Map of property path => error message
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
