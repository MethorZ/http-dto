<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Unit\Response;

use MethorZ\Dto\Response\AbstractDto;

/**
 * Concrete test DTO for testing AbstractDto
 */
final readonly class ConcreteTestDto extends AbstractDto
{
    public function __construct(
        public string $value,
    ) {
    }
}
