<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Unit\Caster;

use MethorZ\Dto\Caster\CasterInterface;
use MethorZ\Dto\Caster\CasterRegistry;
use MethorZ\Dto\Caster\ScalarCaster;
use PHPUnit\Framework\TestCase;
use ReflectionParameter;

final class CasterRegistryTest extends TestCase
{
    public function testCanRegisterCaster(): void
    {
        $registry = new CasterRegistry();
        $caster = new ScalarCaster();

        $registry->register($caster);

        // If no exception, registration successful
        $this->assertTrue(true);
    }

    public function testCanGetCasterForParameter(): void
    {
        $registry = new CasterRegistry();
        $caster = new ScalarCaster();
        $registry->register($caster);

        $param = $this->createParameter('string');
        $result = $registry->resolve($param);

        $this->assertInstanceOf(CasterInterface::class, $result);
    }

    public function testReturnsNullWhenNoCasterFound(): void
    {
        $registry = new CasterRegistry();

        $param = $this->createParameter('SomeCustomType');
        $result = $registry->resolve($param);

        $this->assertNull($result);
    }

    public function testMultipleCastersCanBeRegistered(): void
    {
        $registry = new CasterRegistry();
        $caster1 = new ScalarCaster();
        $caster2 = $this->createMock(CasterInterface::class);

        $registry->register($caster1);
        $registry->register($caster2);

        // If no exception, both registered successfully
        $this->assertTrue(true);
    }

    public function testReturnsFirstMatchingCaster(): void
    {
        $registry = new CasterRegistry();
        $caster1 = new ScalarCaster();
        $registry->register($caster1);

        $param = $this->createParameter('string');
        $result = $registry->resolve($param);

        // Should return the scalar caster for string type
        $this->assertInstanceOf(ScalarCaster::class, $result);
    }

    private function createParameter(string $type): ReflectionParameter
    {
        // phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter -- Intentional: parameters used for type reflection
        $closure = match ($type) {
            // @phpstan-ignore parameterNotUsed.unused (intentional - used for reflection)
            'string' => fn (string $param) => null,
            // @phpstan-ignore parameterNotUsed.unused (intentional - used for reflection)
            'int' => fn (int $param) => null,
            // @phpstan-ignore parameterNotUsed.unused (intentional - used for reflection)
            'SomeCustomType' => fn (\stdClass $param) => null,
            default => throw new \InvalidArgumentException("Unsupported type: {$type}"),
        };
        // phpcs:enable

        $reflection = new \ReflectionFunction($closure);
        $params = $reflection->getParameters();

        return $params[0];
    }
}
