<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Unit\Caster;

use MethorZ\Dto\Caster\CastException;
use PHPUnit\Framework\TestCase;

final class CastExceptionTest extends TestCase
{
    public function testCanCreateException(): void
    {
        $exception = new CastException('Cast failed');

        $this->assertInstanceOf(CastException::class, $exception);
        $this->assertSame('Cast failed', $exception->getMessage());
    }

    public function testExceptionExtendsRuntimeException(): void
    {
        $exception = new CastException('Test');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testCanIncludePreviousException(): void
    {
        $previous = new \Exception('Original error');
        $exception = new CastException('Cast failed', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
