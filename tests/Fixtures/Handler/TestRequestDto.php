<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Fixtures\Handler;

/**
 * Minimal request DTO fixture for testing DtoHandlerWrapper
 */
final readonly class TestRequestDto
{
    public function __construct(
        public string $name = 'test',
    ) {
    }
}
