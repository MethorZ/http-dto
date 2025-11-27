<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Unit\Caster;

use MethorZ\Dto\Caster\ScalarCaster;
use PHPUnit\Framework\TestCase;
use ReflectionParameter;

final class ScalarCasterTest extends TestCase
{
    private ScalarCaster $caster;

    protected function setUp(): void
    {
        $this->caster = new ScalarCaster();
    }

    public function testSupportsStringType(): void
    {
        $param = $this->createParameter('string');
        $this->assertTrue($this->caster->supports($param));
    }

    public function testSupportsIntType(): void
    {
        $param = $this->createParameter('int');
        $this->assertTrue($this->caster->supports($param));
    }

    public function testSupportsFloatType(): void
    {
        $param = $this->createParameter('float');
        $this->assertTrue($this->caster->supports($param));
    }

    public function testSupportsBoolType(): void
    {
        $param = $this->createParameter('bool');
        $this->assertTrue($this->caster->supports($param));
    }

    public function testSupportsArrayType(): void
    {
        // Arrays are now handled by CollectionCaster, not ScalarCaster
        $param = $this->createParameter('array');
        $this->assertFalse($this->caster->supports($param));
    }

    public function testSupportsMixedType(): void
    {
        $param = $this->createParameter('mixed');
        $this->assertTrue($this->caster->supports($param));
    }

    public function testCastsToString(): void
    {
        $param = $this->createParameter('string');
        $result = $this->caster->cast(123, $param);
        $this->assertSame('123', $result);
    }

    public function testCastsToInt(): void
    {
        $param = $this->createParameter('int');
        $result = $this->caster->cast('456', $param);
        $this->assertSame(456, $result);
    }

    public function testCastsToFloat(): void
    {
        $param = $this->createParameter('float');
        $result = $this->caster->cast('12.34', $param);
        $this->assertSame(12.34, $result);
    }

    public function testCastsToBool(): void
    {
        $param = $this->createParameter('bool');
        $this->assertTrue($this->caster->cast(1, $param));
        $this->assertFalse($this->caster->cast(0, $param));
    }

    // testCastsToArray removed - arrays are now handled by CollectionCaster

    public function testHandlesNullableTypes(): void
    {
        $param = $this->createParameter('?string');
        $result = $this->caster->cast(null, $param);
        $this->assertNull($result);
    }

    /**
     * Create a reflection parameter for testing
     *
     * @phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    private function createParameter(string $type): ReflectionParameter
    {
        $closure = match ($type) {
            'string' => fn (string $param) => null,
            'int' => fn (int $param) => null,
            'float' => fn (float $param) => null,
            'bool' => fn (bool $param) => null,
            'array' => fn (array $param) => null,
            'mixed' => fn (mixed $param) => null,
            '?string' => fn (?string $param) => null,
            default => throw new \InvalidArgumentException("Unsupported type: {$type}"),
        };

        $reflection = new \ReflectionFunction($closure);
        $params = $reflection->getParameters();

        return $params[0];
    }
}
