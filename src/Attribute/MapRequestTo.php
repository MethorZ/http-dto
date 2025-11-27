<?php

declare(strict_types=1);

namespace MethorZ\Dto\Attribute;

use Attribute;

/**
 * Attribute to mark a handler for automatic DTO mapping
 *
 * When applied to a RequestHandler, the middleware will automatically
 * map the request to the specified DTO class and attach it to the request.
 *
 * @example
 * #[MapRequestTo(CreateItemRequest::class)]
 * class CreateItemHandler implements RequestHandlerInterface
 * {
 *     public function handle(ServerRequestInterface $request): ResponseInterface
 *     {
 *         $dto = $request->getAttribute('dto');
 *         // Use $dto...
 *     }
 * }
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class MapRequestTo
{
    /**
     * @param class-string<object> $dtoClass The DTO class to map the request to
     * @param string $attributeName The request attribute name to store the DTO (default: 'dto')
     */
    public function __construct(
        public string $dtoClass,
        public string $attributeName = 'dto',
    ) {
    }
}
