<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Unit\Transformer;

use MethorZ\Dto\Transformer\DtoTransformer;
use PHPUnit\Framework\TestCase;

final class DtoTransformerTest extends TestCase
{
    private DtoTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new DtoTransformer();
    }

    public function testConvertsToSnakeCase(): void
    {
        $data = [
            'userId' => '123',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ];

        $result = $this->transformer->toSnakeCase($data);

        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('first_name', $result);
        $this->assertArrayHasKey('last_name', $result);
        $this->assertSame('123', $result['user_id']);
        $this->assertSame('John', $result['first_name']);
    }

    public function testConvertsToCamelCase(): void
    {
        $data = [
            'user_id' => '123',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        $result = $this->transformer->toCamelCase($data);

        $this->assertArrayHasKey('userId', $result);
        $this->assertArrayHasKey('firstName', $result);
        $this->assertArrayHasKey('lastName', $result);
        $this->assertSame('123', $result['userId']);
        $this->assertSame('John', $result['firstName']);
    }

    public function testConvertsNestedArraysToSnakeCase(): void
    {
        $data = [
            'userId' => '123',
            'userProfile' => [
                'firstName' => 'John',
                'homeAddress' => [
                    'streetName' => 'Main St',
                ],
            ],
        ];

        $result = $this->transformer->toSnakeCase($data);

        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('user_profile', $result);
        $this->assertArrayHasKey('first_name', $result['user_profile']);
        $this->assertArrayHasKey('home_address', $result['user_profile']);
        $this->assertArrayHasKey('street_name', $result['user_profile']['home_address']);
    }

    public function testFiltersKeys(): void
    {
        $data = [
            'id' => '123',
            'name' => 'John',
            'password' => 'secret',
            'token' => 'xyz',
        ];

        $result = $this->transformer->filterKeys($data, ['id', 'name']);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('password', $result);
        $this->assertArrayNotHasKey('token', $result);
    }

    public function testExcludesKeys(): void
    {
        $data = [
            'id' => '123',
            'name' => 'John',
            'password' => 'secret',
        ];

        $result = $this->transformer->excludeKeys($data, ['password']);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('password', $result);
    }

    public function testRenamesKeys(): void
    {
        $data = [
            'old_key' => 'value1',
            'another_key' => 'value2',
            'keep_this' => 'value3',
        ];

        $result = $this->transformer->renameKeys($data, [
            'old_key' => 'new_key',
            'another_key' => 'renamed_key',
        ]);

        $this->assertArrayHasKey('new_key', $result);
        $this->assertArrayHasKey('renamed_key', $result);
        $this->assertArrayHasKey('keep_this', $result);
        $this->assertArrayNotHasKey('old_key', $result);
        $this->assertArrayNotHasKey('another_key', $result);
    }

    public function testMasksSensitiveData(): void
    {
        $data = [
            'id' => '123',
            'name' => 'John',
            'password' => 'secret123',
            'token' => 'xyz789',
        ];

        $result = $this->transformer->maskSensitiveData($data, ['password', 'token']);

        $this->assertSame('123', $result['id']);
        $this->assertSame('John', $result['name']);
        $this->assertSame('***', $result['password']);
        $this->assertSame('***', $result['token']);
    }

    public function testMasksSensitiveDataRecursively(): void
    {
        $data = [
            'user' => [
                'id' => '123',
                'password' => 'secret',
            ],
        ];

        $result = $this->transformer->maskSensitiveData($data, ['password']);

        $this->assertSame('***', $result['user']['password']);
    }

    public function testFlattensArray(): void
    {
        $data = [
            'user' => [
                'profile' => [
                    'name' => 'John',
                    'age' => 30,
                ],
                'email' => 'john@example.com',
            ],
        ];

        $result = $this->transformer->flatten($data);

        $this->assertArrayHasKey('user.profile.name', $result);
        $this->assertArrayHasKey('user.profile.age', $result);
        $this->assertArrayHasKey('user.email', $result);
        $this->assertSame('John', $result['user.profile.name']);
        $this->assertSame(30, $result['user.profile.age']);
        $this->assertSame('john@example.com', $result['user.email']);
    }

    public function testUnflattensArray(): void
    {
        $data = [
            'user.profile.name' => 'John',
            'user.profile.age' => 30,
            'user.email' => 'john@example.com',
        ];

        $result = $this->transformer->unflatten($data);

        $this->assertArrayHasKey('user', $result);
        $this->assertIsArray($result['user']);
        $this->assertArrayHasKey('profile', $result['user']);
        $this->assertIsArray($result['user']['profile']);
        $this->assertSame('John', $result['user']['profile']['name']);
        $this->assertSame('john@example.com', $result['user']['email']);
    }
}
