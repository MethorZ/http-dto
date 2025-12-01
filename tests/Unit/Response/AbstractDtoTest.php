<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Unit\Response;

use MethorZ\Dto\Response\AbstractDto;
use PHPUnit\Framework\TestCase;

final class AbstractDtoTest extends TestCase
{
    public function testCanCreateConcreteDto(): void
    {
        $dto = new ConcreteTestDto('test-value');

        $this->assertInstanceOf(AbstractDto::class, $dto);
        $this->assertSame('test-value', $dto->value);
    }

    public function testDtoIsReadonly(): void
    {
        $dto = new ConcreteTestDto('initial');

        $this->assertSame('initial', $dto->value);

        // Readonly class cannot have properties modified - this would throw error at runtime
        $this->expectException(\Error::class);
        $dto->value = 'modified';
    }

    public function testMultiplePropertiesDto(): void
    {
        $dto = new MultiPropertyTestDto('John', 25);

        $this->assertSame('John', $dto->name);
        $this->assertSame(25, $dto->age);
    }
}
