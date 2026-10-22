# methorz/http-dto

**Automatic HTTP ↔ DTO conversion with validation for PSR-15 applications**

[![CI](https://github.com/MethorZ/http-dto/actions/workflows/ci.yml/badge.svg)](https://github.com/MethorZ/http-dto/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/MethorZ/http-dto/graph/badge.svg)](https://codecov.io/gh/MethorZ/http-dto)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org/)
[![PHP Version](https://img.shields.io/badge/php-~8.4.27%20%7C%7C%20~8.5.12-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## What is this?

This package provides **automatic** Data Transfer Object (DTO) handling for PSR-15 middleware applications (Mezzio, Laminas) using the **DtoHandlerWrapper** pattern. It eliminates boilerplate code by:

- 🎯 **Automatically extracting** data from HTTP requests (JSON body, query params, route attributes)
- 🔄 **Automatically mapping** request data to Request DTOs
- ✅ **Automatically validating** Request DTOs using Symfony Validator
- 🚀 **Automatically injecting** validated DTOs as handler parameters
- 📦 **Automatically serializing** Response DTOs to JSON responses

**Key Innovation**: The `DtoHandlerWrapper` pattern wraps your `DtoHandlerInterface` implementations, handling all DTO concerns without requiring global middleware in your pipeline.

## Installation

```bash
composer require methorz/http-dto
```

## Quick Example

### Before (Manual Boilerplate)

```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    // 1. Get request body
    $data = $request->getParsedBody();

    // 2. Map to DTO
    $dto = new CreateItemRequest(
        name: $data['name'] ?? '',
        description: $data['description'] ?? ''
    );

    // 3. Validate DTO
    $violations = $this->validator->validate($dto);
    if (count($violations) > 0) {
        return new JsonResponse(['errors' => ...], 422);
    }

    // 4. Execute service
    $result = $this->service->execute($dto);

    // 5. Serialize response
    return new JsonResponse($result->toArray(), 201);
}
```

### After (Automatic! ✨)

```php
public function __invoke(
    ServerRequestInterface $request,
    CreateItemRequest $dto  // ← Automatically mapped, validated, and injected!
): ItemResponse {           // ← Automatically serialized to JSON!
    return $this->service->execute($dto);  // One line! 🎉
}
```

## Features

### 1. Automatic Request → DTO Mapping

Define Request DTOs with Symfony Validator attributes:

```php
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateItemRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Name is required')]
        #[Assert\Length(min: 3, max: 100)]
        public string $name,

        #[Assert\NotBlank(message: 'Description is required')]
        public string $description,
    ) {}
}
```

### 2. Automatic DTO → Response Serialization

Define Response DTOs with `JsonSerializableDto`:

```php
use Methorz\Dto\Response\JsonSerializableDto;

final readonly class ItemResponse implements JsonSerializableDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }

    public function getStatusCode(): int
    {
        return 201; // Created
    }
}
```

### 3. Handler with Direct DTO Parameters

Implement `DtoHandlerInterface` and use `__invoke()`:

```php
use Methorz\Dto\Handler\DtoHandlerInterface;

final readonly class CreateItemHandler implements DtoHandlerInterface
{
    public function __construct(
        private CreateItemService $service,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        CreateItemRequest $dto  // ← Injected automatically!
    ): ItemResponse {           // ← Serialized automatically!
        return $this->service->execute($dto);
    }

    // PSR-15 compatibility method (not used directly)
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->__invoke($request, new CreateItemRequest('', ''));
    }
}
```

## Setup

### 1. Register DtoHandlerWrapperFactory in ConfigProvider

```php
use MethorZ\Dto\Factory\DtoHandlerWrapperFactory;
use MethorZ\Dto\RequestDtoMapperInterface;
use MethorZ\Dto\Exception\MappingException;
use MethorZ\Dto\Exception\ValidationException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

public function getDependencies(): array
{
    return [
        'factories' => [
            // DTO Handler Wrapper Factory
            DtoHandlerWrapperFactory::class => function (ContainerInterface $container) {
                return new DtoHandlerWrapperFactory(
                    $container->get(RequestDtoMapperInterface::class),
                    $container->get('dto.error_handler'),
                );
            },
        ],
        'services' => [
            // Error handler for DTO validation/mapping failures
            'dto.error_handler' => function (ValidationException|MappingException $e): ResponseInterface {
                if ($e instanceof ValidationException) {
                    return new JsonResponse([
                        'success' => false,
                        'errors' => $e->getErrors(),
                    ], 422);
                }
                return new JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 400);
            },
        ],
    ];
}
```

### 2. Wrap Your Handlers in Routes

Use `DtoHandlerWrapperFactory` to wrap your `DtoHandlerInterface` implementations:

```php
use Item\Application\Handler\CreateItemHandler;
use MethorZ\Dto\Factory\DtoHandlerWrapperFactory;

public function getRoutes(): array
{
    return [
        [
            'allowed_methods' => ['POST'],
            'path'            => '/api/v1/items',
            'middleware'      => [
                DtoHandlerWrapperFactory::class . '::wrap:' . CreateItemHandler::class,
            ],
        ],
    ];
}
```

## Complete Flow

```
HTTP POST /api/items
    ↓
RouteMiddleware (matches route)
    ↓
DispatchMiddleware
    ↓
DtoHandlerWrapper (wraps CreateItemHandler)
    ├─ Extracts DTO class from handler signature
    ├─ Extracts data from request (JSON body, query params, route attributes)
    ├─ Maps data → CreateItemRequest DTO
    ├─ Validates CreateItemRequest (Symfony Validator)
    ├─ Calls: Handler.__invoke(request, CreateItemRequest)
    │   ├─ Handler calls: service.execute($dto)
    │   └─ Handler returns: ItemResponse (implements JsonSerializableDto)
    ├─ Detects: ItemResponse implements JsonSerializableDto
    ├─ Calls: $response->jsonSerialize()
    ├─ Gets: $response->getStatusCode() → 201
    └─ Returns: JsonResponse(data, 201)
    ↓
HTTP Response: 201 Created
{"id": "...", "name": "...", "description": "..."}
```

**Key Benefits of DtoHandlerWrapper Pattern:**
- ✅ **Single pattern** - One component handles request DTO mapping AND response serialization
- ✅ **Handler-specific** - Only processes requests to DtoHandlerInterface implementations
- ✅ **No middleware overhead** - Doesn't process every request in the pipeline
- ✅ **Cleaner architecture** - Clear separation: middleware for cross-cutting, wrapper for handler-specific
- ✅ **Easy to use** - Just wrap your handler in routes configuration

## Error Handling

The middleware automatically handles validation errors:

```json
// HTTP 422 Unprocessable Entity
{
    "status": "error",
    "message": "DTO validation failed",
    "errors": {
        "name": ["Name is required", "Name must be at least 3 characters"],
        "description": ["Description is required"]
    }
}
```

## Benefits

### For Handlers
✅ Return DTOs directly (not `ResponseInterface`)
✅ No `ApiResponse` wrapper calls
✅ No manual `->toArray()` calls
✅ Perfect type safety
✅ Ultra clean (often one line!)

### For Response DTOs
✅ Control their own HTTP status code
✅ Self-serializing (`jsonSerialize()`)
✅ Single Responsibility Principle

### For Testing
✅ Test handler returns actual DTO
✅ No mocking `ApiResponse`
✅ Test serialization separately
✅ More maintainable

### For Architecture
✅ Perfect symmetry: Request DTOs IN, Response DTOs OUT
✅ Consistent pattern across all handlers
✅ Type-safe end-to-end

## Requirements

- PHP 8.4 (>= 8.4.27) or PHP 8.5 (>= 8.5.12)
- PSR-7 (HTTP Message Interface)
- PSR-15 (HTTP Server Middleware)
- Symfony Validator
- Mezzio or any PSR-15 compatible framework

## Related Packages

This package is part of the MethorZ HTTP middleware ecosystem:

| Package | Description |
|---------|-------------|
| **[methorz/http-dto](https://github.com/methorz/http-dto)** | Automatic HTTP ↔ DTO conversion (this package) |
| **[methorz/http-problem-details](https://github.com/methorz/http-problem-details)** | RFC 7807 error handling middleware |
| **[methorz/http-cache-middleware](https://github.com/methorz/http-cache-middleware)** | HTTP caching with ETag support |
| **[methorz/http-request-logger](https://github.com/methorz/http-request-logger)** | Structured logging with request tracking |
| **[methorz/openapi-generator](https://github.com/methorz/openapi-generator)** | Automatic OpenAPI spec generation from DTOs |

These packages work together seamlessly in PSR-15 applications.

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Author

**Thorsten Merz**

---

**Made with ❤️ for clean, type-safe APIs**
