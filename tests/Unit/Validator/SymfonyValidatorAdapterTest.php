<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Unit\Validator;

use MethorZ\Dto\Exception\ValidationException;
use MethorZ\Dto\Validator\SymfonyValidatorAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

final class SymfonyValidatorAdapterTest extends TestCase
{
    private SymfonyValidatorAdapter $adapter;

    protected function setUp(): void
    {
        $symfonyValidator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $this->adapter = new SymfonyValidatorAdapter($symfonyValidator);
    }

    public function testValidatePassesForValidObject(): void
    {
        $dto = new class {
            #[Assert\NotBlank]
            public string $name = 'Valid Name';
        };

        // Should not throw exception
        $this->adapter->validate($dto);
        $this->assertTrue(true);
    }

    public function testValidateThrowsExceptionForInvalidObject(): void
    {
        $dto = new class {
            #[Assert\NotBlank]
            public string $name = '';
        };

        $this->expectException(ValidationException::class);
        $this->adapter->validate($dto);
    }

    public function testValidateIncludesErrorMessages(): void
    {
        $dto = new class {
            #[Assert\NotBlank(message: 'Name cannot be blank')]
            public string $name = '';
        };

        try {
            $this->adapter->validate($dto);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertNotEmpty($errors);
        }
    }
}
