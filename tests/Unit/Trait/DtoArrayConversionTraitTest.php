<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Unit\Trait;

use DateTimeImmutable;
use MethorZ\Dto\Tests\Fixtures\NestedDto;
use MethorZ\Dto\Trait\DtoArrayConversionTrait;
use PHPUnit\Framework\TestCase;

final class DtoArrayConversionTraitTest extends TestCase
{
    public function testConvertsSimpleDtoToArray(): void
    {
        $dto = new class ('test-id', 'Test Name') {
            use DtoArrayConversionTrait;

            public function __construct(
                public string $id,
                public string $name,
            ) {
            }
        };

        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertSame('test-id', $array['id']);
        $this->assertSame('Test Name', $array['name']);
    }

    public function testConvertsNestedDtoToArray(): void
    {
        $address = new NestedDto('123 Main St', 'Springfield', '12345');

        $dto = new class ('user-1', $address) {
            use DtoArrayConversionTrait;

            public function __construct(
                public string $id,
                public NestedDto $address,
            ) {
            }
        };

        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('address', $array);
        $this->assertIsArray($array['address']);
        $this->assertSame('123 Main St', $array['address']['street']);
    }

    public function testConvertsCollectionOfDtosToArray(): void
    {
        $addresses = [
            new NestedDto('123 Main St', 'Springfield', '12345'),
            new NestedDto('456 Elm St', 'Shelbyville', '67890'),
        ];

        $dto = new class ('user-1', $addresses) {
            use DtoArrayConversionTrait;

            /**
             * @param array<int, NestedDto> $addresses
             */
            public function __construct(
                public string $id,
                public array $addresses,
            ) {
            }
        };

        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('addresses', $array);
        $this->assertIsArray($array['addresses']);
        $this->assertCount(2, $array['addresses']);
        $this->assertIsArray($array['addresses'][0]);
        $this->assertSame('123 Main St', $array['addresses'][0]['street']);
    }

    public function testConvertsDateTimeToIso8601(): void
    {
        $date = new DateTimeImmutable('2024-11-26T10:00:00+00:00');

        $dto = new class ('user-1', $date) {
            use DtoArrayConversionTrait;

            public function __construct(
                public string $id,
                public DateTimeImmutable $createdAt,
            ) {
            }
        };

        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('createdAt', $array);
        $this->assertIsString($array['createdAt']);
        $this->assertStringContainsString('2024-11-26T10:00:00', $array['createdAt']);
    }

    public function testCreatesFromArray(): void
    {
        $class = new class ('id', 'name') {
            use DtoArrayConversionTrait;

            public function __construct(
                public string $id,
                public string $name,
            ) {
            }
        };

        $dto = $class::fromArray(['id' => 'test-id', 'name' => 'Test Name']);

        $this->assertSame('test-id', $dto->id);
        $this->assertSame('Test Name', $dto->name);
    }

    public function testFromArrayUsesDefaultValues(): void
    {
        $class = new class ('id', 'active') {
            use DtoArrayConversionTrait;

            public function __construct(
                public string $id,
                public string $status = 'active',
            ) {
            }
        };

        $dto = $class::fromArray(['id' => 'test-id']);

        $this->assertSame('test-id', $dto->id);
        $this->assertSame('active', $dto->status);
    }

    public function testFromArrayThrowsForMissingRequiredParameter(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $class = new class ('id', 'name') {
            use DtoArrayConversionTrait;

            public function __construct(
                public string $id,
                public string $name,
            ) {
            }
        };

        $class::fromArray(['id' => 'test-id']); // Missing 'name'
    }

    public function testJsonSerializeUsesToArray(): void
    {
        $dto = new class ('test-id', 'Test Name') {
            use DtoArrayConversionTrait;

            public function __construct(
                public string $id,
                public string $name,
            ) {
            }
        };

        $json = json_encode($dto);
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('id', $decoded);
        $this->assertSame('test-id', $decoded['id']);
    }
}
