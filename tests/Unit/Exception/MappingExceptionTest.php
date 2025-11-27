<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Unit\Exception;

use MethorZ\Dto\Exception\MappingException;
use PHPUnit\Framework\TestCase;

final class MappingExceptionTest extends TestCase
{
    public function testNoConstructorException(): void
    {
        $exception = MappingException::noConstructor('TestClass');

        $this->assertInstanceOf(MappingException::class, $exception);
        $this->assertStringContainsString('TestClass', $exception->getMessage());
        $this->assertStringContainsString('constructor', $exception->getMessage());
    }

    public function testMissingParameterException(): void
    {
        $exception = MappingException::missingRequiredParameter('TestClass', 'paramName');

        $this->assertInstanceOf(MappingException::class, $exception);
        $this->assertStringContainsString('TestClass', $exception->getMessage());
        $this->assertStringContainsString('paramName', $exception->getMessage());
        $this->assertStringContainsString('missing', $exception->getMessage());
    }

    public function testInstantiationFailedException(): void
    {
        $previous = new \Exception('Original error');
        $exception = MappingException::instantiationFailed('TestClass', $previous);

        $this->assertInstanceOf(MappingException::class, $exception);
        $this->assertStringContainsString('TestClass', $exception->getMessage());
        $this->assertStringContainsString('instantiate', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
