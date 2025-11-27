<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Fixtures;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Simple DTO fixture for testing
 */
final readonly class SimpleRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,

        #[Assert\Range(min: 0, max: 100)]
        public int $age = 0,
    ) {
    }
}

