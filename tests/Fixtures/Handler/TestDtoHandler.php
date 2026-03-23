<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Fixtures\Handler;

use MethorZ\Dto\Handler\DtoHandlerInterface;
use MethorZ\Dto\Response\JsonSerializableDto;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Test handler that returns a JsonSerializableDto
 */
final readonly class TestDtoHandler implements DtoHandlerInterface
{
    public function __construct(
        private JsonSerializableDto $responseDto,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, TestRequestDto $dto): JsonSerializableDto
    {
        return $this->responseDto;
    }
}
