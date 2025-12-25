<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Unit;

use MethorZ\Dto\Exception\MappingException;
use MethorZ\Dto\RequestDtoMapper;
use MethorZ\Dto\Validator\SymfonyValidatorAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Validator\Validation;

/**
 * Example test demonstrating testing infrastructure
 *
 * This will be expanded in Phase 2 with comprehensive coverage
 */
final class RequestDtoMapperTest extends TestCase
{
    private RequestDtoMapper $mapper;

    protected function setUp(): void
    {
        $symfonyValidator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $validator = new SymfonyValidatorAdapter($symfonyValidator);

        $this->mapper = new RequestDtoMapper($validator);
    }

    public function testMapperInitializes(): void
    {
        $this->assertInstanceOf(RequestDtoMapper::class, $this->mapper);
    }

    public function testMapThrowsExceptionForNonExistentClass(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([]);
        $request->method('getAttributes')->willReturn([]);
        $request->method('getQueryParams')->willReturn([]);

        $this->expectException(MappingException::class);
        $this->mapper->map('NonExistentClass', $request);
    }

    public function testRouteParametersAreMappedToDto(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([]);
        $request->method('getQueryParams')->willReturn([]);
        $request->method('getAttributes')->willReturn(['id' => '123']);

        $dto = $this->mapper->map(TestDtoWithId::class, $request);

        $this->assertInstanceOf(TestDtoWithId::class, $dto);
        $this->assertSame('123', $dto->id);
    }

    public function testRouteParametersOverrideBodyParameters(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn(['id' => 'body-id']);
        $request->method('getQueryParams')->willReturn([]);
        $request->method('getAttributes')->willReturn(['id' => 'route-id']);

        $dto = $this->mapper->map(TestDtoWithId::class, $request);

        $this->assertSame('route-id', $dto->id);
    }

    public function testRouteParametersOverrideQueryParameters(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([]);
        $request->method('getQueryParams')->willReturn(['id' => 'query-id']);
        $request->method('getAttributes')->willReturn(['id' => 'route-id']);

        $dto = $this->mapper->map(TestDtoWithId::class, $request);

        $this->assertSame('route-id', $dto->id);
    }

    public function testPsr7InternalAttributesAreExcluded(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([]);
        $request->method('getQueryParams')->willReturn([]);
        $request->method('getAttributes')->willReturn([
            'id' => '123',
            'handler' => 'SomeHandler',
            'middleware' => 'SomeMiddleware',
            '_route' => 'some.route',
            '_route_params' => ['id' => '123'],
            '__route__' => 'route',
            'route' => 'route.name',
            'request-target' => '/test',
            'middleware-matched' => true,
        ]);

        $dto = $this->mapper->map(TestDtoWithId::class, $request);

        // Only 'id' should be mapped
        $this->assertSame('123', $dto->id);
    }

    public function testNonScalarAttributesAreExcluded(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([]);
        $request->method('getQueryParams')->willReturn([]);
        $request->method('getAttributes')->willReturn([
            'id' => '123',
            'object' => new \stdClass(),
            'array' => ['key' => 'value'],
        ]);

        $dto = $this->mapper->map(TestDtoWithId::class, $request);

        // Only scalar 'id' should be mapped
        $this->assertSame('123', $dto->id);
    }
}

// Test DTOs
final readonly class TestDtoWithId
{
    public function __construct(
        public string $id,
    ) {
    }
}
