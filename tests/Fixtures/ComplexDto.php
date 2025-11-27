<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Fixtures;

use DateTimeImmutable;

/**
 * Complex DTO with nested DTOs and collections for testing
 */
final readonly class ComplexDto
{
    /**
     * @param array<int, \MethorZ\Dto\Tests\Fixtures\NestedDto> $items
     */
    public function __construct(
        public string $name,
        public DateTimeImmutable $createdAt,
        public ExampleEnum $status,
        public NestedDto $address,
        public array $items,
    ) {
    }
}

