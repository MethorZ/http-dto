<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Fixtures;

/**
 * Example string-backed enum for testing
 */
enum ExampleEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}

