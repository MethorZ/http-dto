<?php

declare(strict_types=1);

namespace MethorZ\Dto;

use Psr\Http\Message\ServerRequestInterface;

interface RequestDtoMapperInterface
{
    /**
     * Map a PSR-7 request to a DTO instance
     *
     * @template T of object
     * @param class-string<T> $dtoClass The DTO class to instantiate
     * @param ServerRequestInterface $request The PSR-7 request
     * @return T The instantiated and populated DTO
     * @throws Exception\MappingException If mapping fails
     * @throws Exception\ValidationException If validation fails
     */
    public function map(string $dtoClass, ServerRequestInterface $request): object;
}
