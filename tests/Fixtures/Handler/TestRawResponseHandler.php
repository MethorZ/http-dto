<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Fixtures\Handler;

use MethorZ\Dto\Handler\DtoHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Test handler that returns a raw ResponseInterface (e.g. binary file download)
 */
final readonly class TestRawResponseHandler implements DtoHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, TestRequestDto $dto): ResponseInterface
    {
        return $this->response;
    }
}
