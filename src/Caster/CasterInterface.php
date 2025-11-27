<?php

declare(strict_types=1);

namespace MethorZ\Dto\Caster;

use ReflectionParameter;

/**
 * Contract for type casters that transform raw request data into typed values
 *
 * Casters are responsible for converting mixed input data (strings, arrays, etc.)
 * into specific PHP types (objects, enums, dates, etc.).
 */
interface CasterInterface
{
    /**
     * Cast the given value to the target type
     *
     * @param mixed $value The raw value from the request
     * @param ReflectionParameter $parameter The parameter being cast to (provides type info)
     * @return mixed The casted value
     * @throws CastException When casting fails
     */
    public function cast(mixed $value, ReflectionParameter $parameter): mixed;

    /**
     * Check if this caster supports the given parameter type
     *
     * @param ReflectionParameter $parameter The parameter to check
     * @return bool True if this caster can handle the parameter type
     */
    public function supports(ReflectionParameter $parameter): bool;
}
