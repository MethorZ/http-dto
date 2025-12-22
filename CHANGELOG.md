# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

