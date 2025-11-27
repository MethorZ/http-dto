<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Unit\Caster;

use DateTimeImmutable;
use DateTimeInterface;
use MethorZ\Dto\Caster\CastException;
use MethorZ\Dto\Caster\DateTimeCaster;
use PHPUnit\Framework\TestCase;
use ReflectionParameter;

final class DateTimeCasterTest extends TestCase
{
    private DateTimeCaster $caster;

    protected function setUp(): void
    {
        $this->caster = new DateTimeCaster();
    }

    public function testSupportsDateTimeImmutable(): void
    {
        $param = $this->createParameter(DateTimeImmutable::class);
        $this->assertTrue($this->caster->supports($param));
    }

    public function testSupportsDateTimeInterface(): void
    {
        $param = $this->createParameter(DateTimeInterface::class);
        $this->assertTrue($this->caster->supports($param));
    }

    public function testCastsFromIso8601String(): void
    {
        $param = $this->createParameter(DateTimeImmutable::class);
        $result = $this->caster->cast('2024-11-25T10:00:00+00:00', $param);

        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertSame('2024-11-25', $result->format('Y-m-d'));
    }

    public function testCastsFromTimestamp(): void
    {
        $param = $this->createParameter(DateTimeImmutable::class);
        $timestamp = 1732532400; // 2024-11-25 10:00:00 UTC
        $result = $this->caster->cast($timestamp, $param);

        $this->assertInstanceOf(DateTimeImmutable::class, $result);
    }

    public function testReturnsExistingDateTimeInstance(): void
    {
        $param = $this->createParameter(DateTimeImmutable::class);
        $date = new DateTimeImmutable('2024-11-25');
        $result = $this->caster->cast($date, $param);

        $this->assertSame($date, $result);
    }

    public function testHandlesNullableTypes(): void
    {
        $param = $this->createParameter('?' . DateTimeImmutable::class);
        $result = $this->caster->cast(null, $param);

        $this->assertNull($result);
    }

    public function testThrowsExceptionForInvalidString(): void
    {
        $param = $this->createParameter(DateTimeImmutable::class);

        $this->expectException(CastException::class);
        $this->expectExceptionMessage('Failed to cast');

        $this->caster->cast('not-a-date', $param);
    }

    public function testThrowsExceptionForInvalidType(): void
    {
        $param = $this->createParameter(DateTimeImmutable::class);

        $this->expectException(CastException::class);
        $this->caster->cast(['array'], $param);
    }

    /**
     * @param class-string|string $type
     * @phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    private function createParameter(string $type): ReflectionParameter
    {
        $isNullable = str_starts_with($type, '?');
        $actualType = $isNullable ? substr($type, 1) : $type;

        if ($actualType === DateTimeImmutable::class) {
            $closure = $isNullable
                ? fn (?DateTimeImmutable $param) => null
                : fn (DateTimeImmutable $param) => null;
        } elseif ($actualType === DateTimeInterface::class) {
            $closure = $isNullable
                ? fn (?DateTimeInterface $param) => null
                : fn (DateTimeInterface $param) => null;
        } else {
            throw new \InvalidArgumentException("Unsupported type: {$type}");
        }

        $reflection = new \ReflectionFunction($closure);
        return $reflection->getParameters()[0];
    }
}
