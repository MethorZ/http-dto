<?php

declare(strict_types=1);

namespace MethorZ\Dto\Validator;

use MethorZ\Dto\Exception\ValidationException;

/**
 * Framework-agnostic validator interface for DTOs
 *
 * This interface allows using any validation library (Symfony Validator,
 * Respect/Validation, custom validators, etc.) or skipping validation entirely.
 *
 * Example implementations:
 * - SymfonyValidatorAdapter (uses Symfony Validator)
 * - NullValidator (skips validation)
 * - Custom validator implementation
 */
interface ValidatorInterface
{
    /**
     * Validate a DTO object
     *
     * @throws ValidationException If validation fails
     */
    public function validate(object $dto): void;
}
