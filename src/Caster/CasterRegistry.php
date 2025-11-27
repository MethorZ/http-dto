<?php

declare(strict_types=1);

namespace MethorZ\Dto\Caster;

use MethorZ\Dto\Caster\Attribute\CastWith;
use ReflectionAttribute;
use ReflectionParameter;

/**
 * Registry that manages and resolves type casters
 *
 * Casters are resolved in this priority order:
 * 1. Attribute-declared caster (#[CastWith])
 * 2. Explicitly registered caster for the type
 * 3. First matching caster from registered list
 * 4. Null if no caster found (value passes through unchanged)
 */
final class CasterRegistry
{
    /**
     * @var array<int, CasterInterface>
     */
    private array $casters = [];

    /**
     * @var array<class-string, CasterInterface>
     */
    private array $typeCasters = [];

    /**
     * Register a caster with optional type binding
     *
     * @param CasterInterface $caster The caster to register
     * @param class-string|null $forType Bind caster to specific type (optional)
     */
    public function register(CasterInterface $caster, ?string $forType = null): void
    {
        if ($forType !== null) {
            $this->typeCasters[$forType] = $caster;
        }

        $this->casters[] = $caster;
    }

    /**
     * Resolve the appropriate caster for a parameter
     *
     * Returns null if no caster found (value should pass through unchanged)
     */
    public function resolve(ReflectionParameter $parameter): ?CasterInterface
    {
        // Priority 1: Check for #[CastWith] attribute
        $attributeCaster = $this->resolveFromAttribute($parameter);
        if ($attributeCaster !== null) {
            return $attributeCaster;
        }

        // Priority 2: Check for explicitly registered type caster
        $type = $parameter->getType();
        if ($type instanceof \ReflectionNamedType && ! $type->isBuiltin()) {
            $typeName = $type->getName();
            if (isset($this->typeCasters[$typeName])) {
                return $this->typeCasters[$typeName];
            }
        }

        // Priority 3: Find first matching caster
        foreach ($this->casters as $caster) {
            if ($caster->supports($parameter)) {
                return $caster;
            }
        }

        // No caster found
        return null;
    }

    /**
     * Resolve caster from #[CastWith] attribute
     */
    private function resolveFromAttribute(ReflectionParameter $parameter): ?CasterInterface
    {
        $attributes = $parameter->getAttributes(
            CastWith::class,
            ReflectionAttribute::IS_INSTANCEOF,
        );

        if (empty($attributes)) {
            return null;
        }

        $attribute = $attributes[0]->newInstance();
        $casterClass = $attribute->casterClass;

        // Instantiate the caster
        return new $casterClass();
    }
}
