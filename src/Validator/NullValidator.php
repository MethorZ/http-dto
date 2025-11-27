<?php

declare(strict_types=1);

namespace MethorZ\Dto\Validator;

/**
 * Null validator that skips validation
 *
 * Use this when you don't need validation or handle validation
 * elsewhere in your application.
 *
 * Usage:
 * ```php
 * $mapper = new RequestDtoMapper(new NullValidator());
 * ```
 */
final class NullValidator implements ValidatorInterface
{
    /**
     * Skip validation - always passes
     *
     * @param object $dto The DTO to validate (intentionally unused)
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function validate(object $dto): void
    {
        // No validation performed - intentionally unused parameter required by interface
    }
}
