# methorz/http-dto

**Automatic HTTP ↔ DTO conversion via PSR-15 middleware for Mezzio**

[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## What is this?

This package provides **automatic** Data Transfer Object (DTO) handling for PSR-15 middleware applications (Mezzio, Laminas). It eliminates boilerplate code by:

- 🎯 **Automatically mapping** HTTP requests to Request DTOs
- ✅ **Automatically validating** Request DTOs using Symfony Validator
- 🚀 **Automatically injecting** validated DTOs as handler parameters
- 📦 **Automatically serializing** Response DTOs to JSON responses

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

### 1. Register Middlewares in Pipeline

Add to your `config/pipeline.php` **in this order**:

```php
use Methorz\Dto\Middleware\AutoDtoInjectionMiddleware;
use Methorz\Dto\Middleware\AutoJsonResponseMiddleware;

return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    // ... other middleware ...

    $app->pipe(RouteMiddleware::class);

    // Request DTO injection (BEFORE dispatch)
    $app->pipe(AutoDtoInjectionMiddleware::class);

    $app->pipe(DispatchMiddleware::class);

    // Response DTO serialization (AFTER dispatch)
    $app->pipe(AutoJsonResponseMiddleware::class);

    // ... other middleware ...
};
```

### 2. Register in ConfigProvider

```php
use Methorz\Dto\Middleware\AutoDtoInjectionMiddleware;
use Methorz\Dto\Middleware\AutoJsonResponseMiddleware;
use Methorz\Dto\RequestDtoMapperInterface;
use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;

public function getDependencies(): array
{
    return [
        'factories' => [
            RequestDtoMapperInterface::class  => ReflectionBasedAbstractFactory::class,
            AutoDtoInjectionMiddleware::class => ReflectionBasedAbstractFactory::class,
            AutoJsonResponseMiddleware::class => ReflectionBasedAbstractFactory::class,
        ],
    ];
}
```

## Complete Flow

```
HTTP POST /api/items
    ↓
AutoDtoInjectionMiddleware
    ├─ Maps request → CreateItemRequest
    ├─ Validates CreateItemRequest
    └─ Injects as parameter
    ↓
Handler.__invoke(request, CreateItemRequest)
    ├─ Calls: service.execute($dto)
    └─ Returns: ItemResponse
    ↓
AutoJsonResponseMiddleware
    ├─ Detects: ItemResponse implements JsonSerializableDto
    ├─ Calls: $response->jsonSerialize()
    ├─ Gets: $response->getStatusCode() → 201
    └─ Returns: JsonResponse(data, 201)
    ↓
HTTP Response: 201 Created
{"id": "...", "name": "...", "description": "..."}
```

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

- PHP 8.2+
- PSR-7 (HTTP Message Interface)
- PSR-15 (HTTP Server Middleware)
- Symfony Validator
- Mezzio or any PSR-15 compatible framework

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Author

**Thorsten Merz**
Website: [methorz.com](https://methorz.com)

---

**Made with ❤️ for clean, type-safe APIs**
