<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Unit\Cache;

use MethorZ\Dto\Cache\ReflectionCache;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ReflectionCacheTest extends TestCase
{
    private ReflectionCache $cache;

    protected function setUp(): void
    {
        $this->cache = new ReflectionCache();
    }

    public function testGetReflectionReturnsReflectionClass(): void
    {
        $reflection = $this->cache->getReflection(\stdClass::class);

        $this->assertInstanceOf(ReflectionClass::class, $reflection);
        $this->assertSame(\stdClass::class, $reflection->getName());
    }

    public function testGetReflectionCachesResult(): void
    {
        $first = $this->cache->getReflection(\stdClass::class);
        $second = $this->cache->getReflection(\stdClass::class);

        // Should return the same instance (cached)
        $this->assertSame($first, $second);
    }

    public function testGetConstructorParametersReturnsParameters(): void
    {
        $testClass = new class ('test') {
            public function __construct(public string $param)
            {
            }
        };

        $className = $testClass::class;
        $parameters = $this->cache->getConstructorParameters($className);

        $this->assertIsArray($parameters);
        $this->assertCount(1, $parameters);
        $this->assertSame('param', $parameters[0]->getName());
    }

    public function testGetConstructorParametersForClassWithoutConstructor(): void
    {
        $parameters = $this->cache->getConstructorParameters(\stdClass::class);

        $this->assertIsArray($parameters);
        $this->assertEmpty($parameters);
    }

    public function testGetConstructorParametersCachesResult(): void
    {
        $testClass = new class ('test') {
            public function __construct(public string $param)
            {
            }
        };

        $className = $testClass::class;
        $first = $this->cache->getConstructorParameters($className);
        $second = $this->cache->getConstructorParameters($className);

        // Should return the same array reference (cached)
        $this->assertSame($first, $second);
    }

    public function testGetReflectionForDifferentClassesReturnsDifferentInstances(): void
    {
        $reflection1 = $this->cache->getReflection(\stdClass::class);
        $reflection2 = $this->cache->getReflection(\ArrayObject::class);

        $this->assertNotSame($reflection1, $reflection2);
        $this->assertSame(\stdClass::class, $reflection1->getName());
        $this->assertSame(\ArrayObject::class, $reflection2->getName());
    }
}
