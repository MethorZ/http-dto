<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Fixtures;

use MethorZ\Dto\Trait\DtoArrayConversionTrait;

/**
 * Example nested DTO for testing
 */
final readonly class NestedDto
{
    use DtoArrayConversionTrait;

    public function __construct(
        public string $street,
        public string $city,
        public string $zipCode,
    ) {
    }
}

