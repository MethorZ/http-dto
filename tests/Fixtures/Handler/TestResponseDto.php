<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Fixtures\Handler;

use MethorZ\Dto\Response\AbstractDto;

/**
 * Minimal response DTO fixture for testing DtoHandlerWrapper
 */
final readonly class TestResponseDto extends AbstractDto
{
    public function __construct(
        public string $value,
    ) {
    }
}
