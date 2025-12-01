<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Unit\Response;

use MethorZ\Dto\Response\AbstractDto;

/**
 * Multi-property test DTO
 */
final readonly class MultiPropertyTestDto extends AbstractDto
{
    public function __construct(
        public string $name,
        public int $age,
    ) {
    }
}
