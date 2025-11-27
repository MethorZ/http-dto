<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Unit\Caster;

use MethorZ\Dto\Caster\CollectionCaster;
use MethorZ\Dto\Caster\CasterRegistry;
use MethorZ\Dto\Caster\DtoCaster;
use MethorZ\Dto\Tests\Fixtures\NestedDto;
use PHPUnit\Framework\TestCase;
use ReflectionParameter;

final class CollectionCasterTest extends TestCase
{
    public function testCastsCollectionOfDtos(): void
    {
        $registry = new CasterRegistry();
        $dtoCaster = new DtoCaster($registry);
        $registry->register($dtoCaster);

        $caster = new CollectionCaster($registry, $dtoCaster);

        // Create a test parameter with PHPDoc array<int, NestedDto>
        $parameter = $this->createParameter();

        $value = [
            ['street' => '123 Main St', 'city' => 'Test City', 'zipCode' => '12345'],
            ['street' => '456 Elm St', 'city' => 'Another City', 'zipCode' => '67890'],
        ];

        $result = $caster->cast($value, $parameter);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(NestedDto::class, $result[0]);
        $this->assertSame('123 Main St', $result[0]->street);
        $this->assertInstanceOf(NestedDto::class, $result[1]);
        $this->assertSame('456 Elm St', $result[1]->street);
    }

    /**
     * Helper method to create a ReflectionParameter with array<NestedDto> type hint
     *
     * @param array<int, \MethorZ\Dto\Tests\Fixtures\NestedDto> $items
     * @phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    private function createParameter(array $items = []): ReflectionParameter
    {
        $reflection = new \ReflectionMethod($this, 'createParameter');
        return $reflection->getParameters()[0];
    }
}
