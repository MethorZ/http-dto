<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Unit\Response;

use MethorZ\Dto\Response\AbstractDto;
use PHPUnit\Framework\TestCase;

final class AbstractDtoTest extends TestCase
{
    public function testCanCreateConcreteDto(): void
    {
        $dto = new readonly class ('test-value') extends AbstractDto {
            public function __construct(public string $value)
            {
            }
        };

        $this->assertInstanceOf(AbstractDto::class, $dto);
        $this->assertSame('test-value', $dto->value);
    }

    public function testDtoIsReadonly(): void
    {
        $dto = new readonly class ('initial') extends AbstractDto {
            public function __construct(public string $value)
            {
            }
        };

        $this->assertSame('initial', $dto->value);

        // Readonly class cannot have properties modified - this would throw error at runtime
        $this->expectException(\Error::class);
        $dto->value = 'modified';
    }

    public function testMultiplePropertiesDto(): void
    {
        $dto = new readonly class ('John', 25) extends AbstractDto {
            public function __construct(
                public string $name,
                public int $age,
            ) {
            }
        };

        $this->assertSame('John', $dto->name);
        $this->assertSame(25, $dto->age);
    }
}
