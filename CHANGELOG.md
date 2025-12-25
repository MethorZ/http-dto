# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.0] - 2025-12-25

### Added
- Route parameter support: Route parameters (e.g., `{id}` in `/articles/{id}`) are now automatically mapped to DTO properties
- Enhanced attribute filtering: PSR-7 and framework internal attributes are now properly excluded from DTO mapping
- Only scalar route parameters are included in DTO mapping (objects and arrays are excluded)

### Changed
- **Priority order for request data** is now clearly defined:
  - Query parameters (lowest priority)
  - Request body (JSON/form data)
  - Route parameters (highest priority - always wins)
- Expanded list of excluded PSR-7 internal attributes to prevent mapping conflicts

### Technical Details
- Updated `RequestDtoMapper::extractRequestData()` to filter out more internal attributes
- Added: `handler`, `middleware`, `_route`, `_route_params`, `__route__`, `route` to exclusion list
- Non-scalar attribute values are now filtered out automatically

This is a backwards-compatible feature addition. No breaking changes.

## [2.0.0] - 2025-12-22

### Changed
- **BREAKING**: `DtoHandlerInterface` is now a marker interface (empty)
- Implementations must add type hints to their `$dto` parameter
- Removed PHPDoc `@param` fallback for DTO type discovery - type hints are now required
- `DtoHandlerWrapper`, `AutoDtoInjectionMiddleware`, and `DtoParameterInjectionMiddleware` now require typed DTO parameters
- Simplified error messages for missing type hints

### Removed
- PHPDoc parsing logic for DTO type discovery
- `resolveClassFromUseStatements()` method (no longer needed)

### Added
- `UPGRADE.md` with migration guide from v1.x

### Benefits
- True type safety with native PHP type hints
- Perfect IDE autocomplete without PHPDoc workarounds
- Static analysis catches type errors at build time
- Simpler, more maintainable code

## [1.0.0] - 2025-11-27

### Added
- Initial release of HTTP DTO library
- Automatic HTTP ↔ DTO conversion via PSR-15 middleware
- JSON, XML, URL-encoded data support
- Symfony Validator integration
- UUID type conversion
- Enum type conversion
- DateTime/DateTimeImmutable conversion
- Custom type converter support
- Flexible validation adapters
- Request body parsing and DTO hydration
- DTO serialization to PSR-7 responses
- Comprehensive test suite
- Complete documentation with examples

[1.0.0]: https://github.com/methorz/http-dto/releases/tag/v1.0.0

