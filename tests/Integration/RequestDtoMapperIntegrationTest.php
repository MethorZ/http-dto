<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Integration;

use DateTimeImmutable;
use Nyholm\Psr7\ServerRequest;
use MethorZ\Dto\RequestDtoMapper;
use MethorZ\Dto\Tests\Fixtures\ComplexDto;
use MethorZ\Dto\Tests\Fixtures\ExampleEnum;
use MethorZ\Dto\Tests\Fixtures\NestedDto;
use MethorZ\Dto\Validator\SymfonyValidatorAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

/**
 * Integration test demonstrating full caster system functionality
 */
final class RequestDtoMapperIntegrationTest extends TestCase
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

    public function testMapsComplexDtoWithAllCasters(): void
    {
        // Prepare request data with nested structures
        $requestData = [
            'name' => 'Test Order',
            'createdAt' => '2024-11-25T10:00:00+00:00',
            'status' => 'active',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
                'zipCode' => '12345',
            ],
            'items' => [
                [
                    'street' => '456 Elm St',
                    'city' => 'Shelbyville',
                    'zipCode' => '67890',
                ],
            ],
        ];

        $request = (new ServerRequest('POST', '/'))
            ->withParsedBody($requestData);

        // Map to complex DTO
        $dto = $this->mapper->map(ComplexDto::class, $request);

        // Verify all casters worked correctly
        $this->assertInstanceOf(ComplexDto::class, $dto);
        $this->assertSame('Test Order', $dto->name);
        $this->assertInstanceOf(DateTimeImmutable::class, $dto->createdAt);
        $this->assertSame(ExampleEnum::ACTIVE, $dto->status);
        $this->assertInstanceOf(NestedDto::class, $dto->address);
        $this->assertSame('123 Main St', $dto->address->street);
        $this->assertIsArray($dto->items);
        $this->assertCount(1, $dto->items);
        $this->assertInstanceOf(NestedDto::class, $dto->items[0]);
        $this->assertSame('456 Elm St', $dto->items[0]->street);
    }
}
