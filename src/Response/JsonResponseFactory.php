<?php

declare(strict_types=1);

namespace MethorZ\Dto\Response;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

use function json_encode;

use const JSON_HEX_AMP;
use const JSON_HEX_APOS;
use const JSON_HEX_QUOT;
use const JSON_HEX_TAG;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

/**
 * Framework-agnostic JSON Response Factory
 *
 * Creates JSON responses using PSR-17 factories, making it compatible
 * with any PSR-7 implementation (laminas-diactoros, nyholm/psr7, guzzle, etc.)
 *
 * This replaces the concrete JsonResponse class that was tightly coupled
 * to a specific PSR-7 implementation.
 */
final readonly class JsonResponseFactory
{
    private const DEFAULT_JSON_FLAGS = JSON_HEX_TAG
        | JSON_HEX_APOS
        | JSON_HEX_AMP
        | JSON_HEX_QUOT
        | JSON_UNESCAPED_SLASHES
        | JSON_THROW_ON_ERROR;

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    /**
     * Create a JSON response from data
     *
     * @param mixed $data Data to encode as JSON
     * @param int $status HTTP status code
     * @param array<string, string|string[]> $headers Additional headers
     * @param int $encodingOptions JSON encoding options
     */
    public function create(
        mixed $data,
        int $status = 200,
        array $headers = [],
        int $encodingOptions = self::DEFAULT_JSON_FLAGS,
    ): ResponseInterface {
        $json = json_encode($data, $encodingOptions);
        $body = $this->streamFactory->createStream($json);

        $response = $this->responseFactory->createResponse($status)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($body);

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    /**
     * Create a JSON response from a JsonSerializableDto
     */
    public function fromDto(JsonSerializableDto $dto): ResponseInterface
    {
        return $this->create(
            $dto->jsonSerialize(),
            $dto->getStatusCode(),
        );
    }
}
