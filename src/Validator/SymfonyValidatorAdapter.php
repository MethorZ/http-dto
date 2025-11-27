<?php

declare(strict_types=1);

namespace MethorZ\Dto\Validator;

use MethorZ\Dto\Exception\ValidationException;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;

/**
 * Adapter for Symfony Validator
 *
 * Bridges Symfony's ValidatorInterface to our framework-agnostic ValidatorInterface.
 *
 * Requires: composer require symfony/validator
 *
 * Usage:
 * ```php
 * use Symfony\Component\Validator\Validation;
 *
 * $symfonyValidator = Validation::createValidatorBuilder()
 *     ->enableAttributeMapping()
 *     ->getValidator();
 *
 * $adapter = new SymfonyValidatorAdapter($symfonyValidator);
 * $mapper = new RequestDtoMapper($adapter);
 * ```
 */
final readonly class SymfonyValidatorAdapter implements ValidatorInterface
{
    public function __construct(
        private SymfonyValidatorInterface $validator,
    ) {
    }

    /**
     * Validate using Symfony Validator
     *
     * @throws ValidationException If validation fails
     */
    public function validate(object $dto): void
    {
        $violations = $this->validator->validate($dto);

        if ($violations->count() > 0) {
            // Convert Symfony violations to framework-agnostic errors array
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = (string) $violation->getMessage();
            }

            throw ValidationException::fromErrors($errors);
        }
    }
}
