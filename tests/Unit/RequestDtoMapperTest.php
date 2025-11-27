<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Unit;

use MethorZ\Dto\Exception\MappingException;
use MethorZ\Dto\RequestDtoMapper;
use MethorZ\Dto\Validator\SymfonyValidatorAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Validator\Validation;

/**
 * Example test demonstrating testing infrastructure
 *
 * This will be expanded in Phase 2 with comprehensive coverage
 */
final class RequestDtoMapperTest extends TestCase
{
    private RequestDtoMapper $mapper;

    protected function setUp(): void
    {
        $symfonyValidator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $validator = new SymfonyValidatorAdapter($symfonyValidator);

        $this->mapper = new RequestDtoMapper($validator);
    }

    public function testMapperInitializes(): void
    {
        $this->assertInstanceOf(RequestDtoMapper::class, $this->mapper);
    }

    public function testMapThrowsExceptionForNonExistentClass(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([]);
        $request->method('getAttributes')->willReturn([]);

        $this->expectException(MappingException::class);
        $this->mapper->map('NonExistentClass', $request);
    }
}
