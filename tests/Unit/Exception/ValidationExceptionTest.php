<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Unit\Exception;

use MethorZ\Dto\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

final class ValidationExceptionTest extends TestCase
{
    public function testFromErrorsCreatesException(): void
    {
        $errors = [
            'name' => 'Value is required',
        ];

        $exception = ValidationException::fromErrors($errors);

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertStringContainsString('validation failed', $exception->getMessage());
    }

    public function testGetErrorsReturnsErrorArray(): void
    {
        $errors = [
            'name' => 'Value is required',
            'username' => 'Must be at least 3 characters',
        ];

        $exception = ValidationException::fromErrors($errors);
        $retrievedErrors = $exception->getErrors();

        $this->assertIsArray($retrievedErrors);
        $this->assertArrayHasKey('name', $retrievedErrors);
        $this->assertArrayHasKey('username', $retrievedErrors);
        $this->assertSame('Value is required', $retrievedErrors['name']);
        $this->assertSame('Must be at least 3 characters', $retrievedErrors['username']);
    }

    public function testFromEmptyErrorsCreatesException(): void
    {
        $errors = [];

        $exception = ValidationException::fromErrors($errors);
        $retrievedErrors = $exception->getErrors();

        $this->assertIsArray($retrievedErrors);
        $this->assertEmpty($retrievedErrors);
    }

    public function testConstructorSetsMessage(): void
    {
        $errors = ['field' => 'error message'];
        $exception = ValidationException::fromErrors($errors, 'Custom validation message');

        $this->assertSame('Custom validation message', $exception->getMessage());
        $this->assertSame($errors, $exception->getErrors());
    }
}
