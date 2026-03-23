<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Fixtures;

/**
 * Test DTO fixture with an ID property for route parameter mapping tests
 */
final readonly class TestDtoWithId
{
    public function __construct(
        public string $id,
    ) {
    }
}
