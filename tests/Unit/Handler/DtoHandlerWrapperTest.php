<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Unit\Handler;

use MethorZ\Dto\Exception\ValidationException;
use MethorZ\Dto\Handler\DtoHandlerWrapper;
use MethorZ\Dto\RequestDtoMapperInterface;
use MethorZ\Dto\Response\JsonResponseFactory;
use MethorZ\Dto\Tests\Fixtures\Handler\TestDtoHandler;
use MethorZ\Dto\Tests\Fixtures\Handler\TestErrorHandler;
use MethorZ\Dto\Tests\Fixtures\Handler\TestRawResponseHandler;
use MethorZ\Dto\Tests\Fixtures\Handler\TestRequestDto;
use MethorZ\Dto\Tests\Fixtures\Handler\TestResponseDto;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Tests for DtoHandlerWrapper
 *
 * Covers:
 * - JsonSerializableDto responses are serialized to JSON
 * - Raw ResponseInterface responses are passed through unchanged
 * - ValidationException routes through the error handler
 */
final class DtoHandlerWrapperTest extends TestCase
{
    private Psr17Factory $psr17Factory;
    private JsonResponseFactory $jsonResponseFactory;

    protected function setUp(): void
    {
        $this->psr17Factory = new Psr17Factory();
        $this->jsonResponseFactory = new JsonResponseFactory($this->psr17Factory, $this->psr17Factory);
    }

    public function testJsonSerializableDtoResponseIsSerializedToJson(): void
    {
        $responseDto = new TestResponseDto('test-value');
        $requestDto = new TestRequestDto('request');
        $request = $this->createMock(ServerRequestInterface::class);

        $mapper = $this->createMock(RequestDtoMapperInterface::class);
        $mapper->method('map')->willReturn($requestDto);

        $errorHandler = new TestErrorHandler($this->psr17Factory->createResponse(422));
        $wrapper = new DtoHandlerWrapper(
            new TestDtoHandler($responseDto),
            $mapper,
            $this->jsonResponseFactory,
            $errorHandler,
        );
        $result = $wrapper->handle($request);

        $this->assertSame(200, $result->getStatusCode());
        $this->assertStringContainsString('application/json', $result->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('test-value', (string) $result->getBody());
    }

    public function testRawResponseInterfaceIsPassedThroughUnchanged(): void
    {
        $rawResponse = $this->psr17Factory->createResponse(200)
            ->withHeader('Content-Type', 'image/png')
            ->withHeader('Content-Disposition', 'inline; filename="chart.png"');

        $requestDto = new TestRequestDto('request');
        $request = $this->createMock(ServerRequestInterface::class);

        $mapper = $this->createMock(RequestDtoMapperInterface::class);
        $mapper->method('map')->willReturn($requestDto);

        $errorHandler = new TestErrorHandler($this->psr17Factory->createResponse(422));
        $wrapper = new DtoHandlerWrapper(
            new TestRawResponseHandler($rawResponse),
            $mapper,
            $this->jsonResponseFactory,
            $errorHandler,
        );
        $result = $wrapper->handle($request);

        $this->assertSame($rawResponse, $result);
        $this->assertSame('image/png', $result->getHeaderLine('Content-Type'));
        $this->assertSame('inline; filename="chart.png"', $result->getHeaderLine('Content-Disposition'));
        $this->assertNull($errorHandler->capturedError);
    }

    public function testValidationExceptionCallsErrorHandler(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $errorResponse = $this->psr17Factory->createResponse(422);

        $mapper = $this->createMock(RequestDtoMapperInterface::class);
        $mapper->method('map')->willThrowException(ValidationException::fromErrors(['name' => 'Required']));

        $errorHandler = new TestErrorHandler($errorResponse);
        $wrapper = new DtoHandlerWrapper(
            new TestDtoHandler(new TestResponseDto('test')),
            $mapper,
            $this->jsonResponseFactory,
            $errorHandler,
        );
        $result = $wrapper->handle($request);

        $this->assertSame($errorResponse, $result);
        $this->assertInstanceOf(ValidationException::class, $errorHandler->capturedError);
    }
}
