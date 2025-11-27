<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Unit\Validator;

use MethorZ\Dto\Validator\NullValidator;
use PHPUnit\Framework\TestCase;

final class NullValidatorTest extends TestCase
{
    private NullValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new NullValidator();
    }

    public function testValidateDoesNotThrowException(): void
    {
        $dto = new \stdClass();
        $dto->property = 'value';

        // Should not throw any exception
        $this->validator->validate($dto);

        // If we get here without exception, test passes
        $this->assertTrue(true);
    }

    public function testValidateAcceptsAnyObject(): void
    {
        $objects = [
            new \stdClass(),
            new \ArrayObject(),
            new class {
                public string $test = 'value';
            },
        ];

        foreach ($objects as $object) {
            $this->validator->validate($object);
        }

        // If we get here without exception, test passes
        $this->assertTrue(true);
    }
}
