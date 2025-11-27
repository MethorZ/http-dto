# ADR 001: Caster System Architecture

**Status:** Proposed
**Date:** 2024-11-25
**Deciders:** Thorsten Merz
**Context:** Phase 2 of DTO Enhancement Plan

## Context and Problem Statement

The current `RequestDtoMapper` only supports basic PHP scalar type casting (string, int, float, bool, array). We need to support complex type transformations such as:

- Value Objects (Uuid, Email, Money)
- DateTimeImmutable / Carbon instances
- Nested DTOs
- Collections of objects
- Custom domain-specific types

**How can we design an extensible type casting system that maintains type safety, performance, and developer experience?**

## Considered Options

### Option 1: Reflection-Based Auto-Discovery
**Approach:** Automatically detect and cast types based on reflection and constructor signatures.

**Pros:**
- Zero configuration for standard cases
- Works out-of-the-box for DTOs with proper type hints
- Maintains type safety through reflection

**Cons:**
- Limited to types with public constructors
- Cannot handle complex transformations
- Reflection overhead on every request

### Option 2: Interface-Based Caster System (Spatie Approach)
**Approach:** Define a `Caster` interface, allow registration of custom casters for specific types.

**Pros:**
- Explicit and predictable
- Highly extensible
- Can handle any transformation logic
- Easy to test casters in isolation

**Cons:**
- Requires registration boilerplate
- Manual configuration needed

### Option 3: Attribute-Based Casting
**Approach:** Use PHP 8+ attributes to declare casting behavior directly on DTO properties.

**Pros:**
- Declaration close to usage
- Self-documenting
- No separate registration needed
- Modern PHP approach

**Cons:**
- Limited to property-level transformations
- Cannot share casting logic easily
- Couples DTOs to casting implementation

### Option 4: Hybrid System (Our Chosen Approach)
**Approach:** Combine auto-detection with explicit caster registration and attribute overrides.

**Pros:**
- Best of all worlds
- Zero config for common cases
- Explicit when needed
- Extensible and performant

**Cons:**
- More complex implementation
- Multiple ways to achieve the same thing (requires clear documentation)

## Decision

**We choose Option 4: Hybrid System** with the following design:

### Architecture

```
┌─────────────────────────────────────────────────────────┐
│                  RequestDtoMapper                       │
│  - Entry point for DTO mapping                          │
│  - Orchestrates casters and validation                  │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│                 CasterRegistry                          │
│  - Manages registered casters                           │
│  - Resolves which caster to use for a type              │
│  - Supports priority/fallback                           │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│                  Caster Interface                       │
│  - public function cast(mixed $value): mixed            │
│  - public function supports(string $type): bool         │
└─────────────────────────────────────────────────────────┘
                     │
        ┌────────────┼────────────┬──────────────┐
        ▼            ▼            ▼              ▼
  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────┐
  │  Uuid    │ │ DateTime │ │  Nested  │ │  Collection  │
  │  Caster  │ │  Caster  │ │   DTO    │ │    Caster    │
  │          │ │          │ │  Caster  │ │              │
  └──────────┘ └──────────┘ └──────────┘ └──────────────┘
```

### Key Components

1. **`CasterInterface`**
   ```php
   interface CasterInterface
   {
       public function cast(mixed $value, ReflectionParameter $parameter): mixed;
       public function supports(ReflectionParameter $parameter): bool;
   }
   ```

2. **`CasterRegistry`**
   - Maintains list of registered casters
   - Resolves appropriate caster for a given type
   - Supports priority ordering

3. **Built-in Casters**
   - `ScalarCaster` - string, int, float, bool (current behavior)
   - `DateTimeCaster` - DateTimeImmutable, Carbon
   - `UuidCaster` - Ramsey\Uuid\UuidInterface
   - `DtoCaster` - Nested DTO support
   - `CollectionCaster` - Arrays of typed objects
   - `EnumCaster` - Backed enums

4. **Attribute-Based Override**
   ```php
   #[CastWith(UuidCaster::class)]
   public Uuid $id;
   ```

### Casting Priority

1. **Attribute-declared caster** (`#[CastWith]`) - highest priority
2. **Explicitly registered caster** for the specific type
3. **Auto-detected caster** based on type hint
4. **Fallback to value as-is** if no caster found

## Consequences

### Positive

- ✅ **Zero config for 90% of use cases** - Auto-detection handles common types
- ✅ **Extensible** - Developers can add custom casters for domain types
- ✅ **Type-safe** - Uses PHP reflection and type hints
- ✅ **Testable** - Each caster is a discrete, testable unit
- ✅ **Performance** - Caster resolution happens once per parameter, then cached
- ✅ **PSR-compliant** - No framework lock-in

### Negative

- ❌ **Complexity** - More moving parts than simple scalar casting
- ❌ **Learning curve** - Developers need to understand caster system
- ❌ **Potential for confusion** - Multiple ways to achieve casting

### Mitigation Strategies

1. **Comprehensive Documentation**
   - Clear examples for common use cases
   - When to use each casting method
   - Best practices guide

2. **Sensible Defaults**
   - Ship with all common casters pre-registered
   - Auto-detection covers 90% of cases

3. **Clear Error Messages**
   - When casting fails, explain why and suggest solutions
   - Include type information in exceptions

## Implementation Plan

**Phase 2 Tasks:**
1. Create `CasterInterface`
2. Implement `CasterRegistry` with priority support
3. Build core casters (Scalar, DateTime, Uuid)
4. Integrate into `RequestDtoMapper`
5. Add comprehensive tests
6. Document with examples

## References

- Spatie Data Transfer Object: https://github.com/spatie/data-transfer-object
- PHP 8.1+ Features: https://www.php.net/releases/8.1/en.php
- Symfony Property Access: https://symfony.com/doc/current/components/property_access.html

## Notes

This ADR will be updated as implementation progresses and we discover edge cases or optimization opportunities.

