<?php

declare(strict_types=1);

namespace MethorZ\Dto\Caster\Attribute;

use Attribute;
use MethorZ\Dto\Caster\CasterInterface;

/**
 * Attribute to specify a custom caster for a DTO property
 *
 * Usage:
 * ```php
 * final readonly class MyRequest
 * {
 *     public function __construct(
 *         #[CastWith(MoneyCaster::class)]
 *         public Money $price,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final readonly class CastWith
{
    /**
     * @param class-string<CasterInterface> $casterClass
     */
    public function __construct(
        public string $casterClass,
    ) {
    }
}
