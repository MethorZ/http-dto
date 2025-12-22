# Upgrade Guide

## Upgrading from v1.x to v2.0

Version 2.0 introduces a **breaking change** that simplifies handler signatures and enables true type safety.

### What Changed

**`DtoHandlerInterface` is now a marker interface.**

In v1.x, the interface defined:
```php
interface DtoHandlerInterface
{
    public function __invoke(ServerRequestInterface $request, $dto): JsonSerializableDto;
}
```

In v2.0, it's now an empty marker interface:
```php
interface DtoHandlerInterface
{
    // Marker interface - implementations define their own __invoke() signature
}
```

### Why This Change?

PHP's contravariance rules prevented implementations from adding type hints to the `$dto` parameter. By making the interface a marker, implementations can now use **fully-typed DTO parameters**:

```php
// v1.x - Untyped parameter, required PHPDoc for type discovery
/**
 * @param CreateItemRequest $dto
 */
public function __invoke(ServerRequestInterface $request, $dto): JsonSerializableDto
{
    /** @var CreateItemRequest $dto */
    // ...
}

// v2.0 - Fully typed parameter!
public function __invoke(ServerRequestInterface $request, CreateItemRequest $dto): JsonSerializableDto
{
    // No PHPDoc or @var needed!
}
```

### Migration Steps

1. **Add type hints** to your `$dto` parameters:

   Before:
   ```php
   /**
    * @param CreateItemRequest $dto
    */
   public function __invoke(
       ServerRequestInterface $request,
       $dto
   ): JsonSerializableDto {
       /** @var CreateItemRequest $dto */
       return $this->service->create($dto);
   }
   ```

   After:
   ```php
   public function __invoke(
       ServerRequestInterface $request,
       CreateItemRequest $dto  // Now typed!
   ): JsonSerializableDto {
       return $this->service->create($dto);
   }
   ```

2. **Remove** `assert($dto instanceof YourDto)` statements (no longer needed)

3. **Remove** `/** @var YourDto $dto */` annotations (no longer needed)

4. **Optionally remove** `@param` PHPDoc if only used for type hinting

### Benefits

- **True type safety**: PHP enforces the DTO type at runtime
- **Perfect IDE support**: Full autocomplete without PHPDoc workarounds
- **Static analysis**: PHPStan/Psalm catch type errors at build time
- **Simpler code**: No PHPDoc annotations or assertions needed
- **Better refactoring**: Rename/move DTOs with full IDE support

### Error Messages

If you forget to add a type hint, you'll see this helpful error:

```
The $dto parameter in YourHandler::__invoke() must have a class type hint. Found: no type
```

Simply add the type hint to fix it.

### Compatibility

- **PHP 8.1+**: Required (same as v1.x)
- **Mezzio 3.x**: Fully compatible
- **Symfony Validator**: Optional, works as before

### Questions?

If you encounter issues during migration, please [open an issue](https://github.com/methorz/http-dto/issues).

