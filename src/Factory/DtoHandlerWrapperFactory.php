<?php

declare(strict_types=1);

namespace MethorZ\Dto\Factory;

use MethorZ\Dto\Exception\MappingException;
use MethorZ\Dto\Exception\ValidationException;
use MethorZ\Dto\Handler\DtoHandlerInterface;
use MethorZ\Dto\Handler\DtoHandlerWrapper;
use MethorZ\Dto\RequestDtoMapperInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Factory for creating DtoHandlerWrapper instances
 *
 * This factory simplifies the integration of DTO handlers into your DI container
 * by providing a centralized place to configure error handling and dependencies.
 *
 * Usage in Laminas ServiceManager:
 * ```php
 * 'factories' => [
 *     MyDtoHandler::class => function($container) {
 *         $handler = new MyDtoHandler(...);
 *         return $container->get(DtoHandlerWrapperFactory::class)->wrap($handler);
 *     },
 * ]
 * ```
 */
final readonly class DtoHandlerWrapperFactory
{
    /**
     * @param callable(ValidationException|MappingException): ResponseInterface $errorHandler
     */
    public function __construct(
        private RequestDtoMapperInterface $dtoMapper,
        private mixed $errorHandler,
    ) {
    }

    /**
     * Invoke factory from container
     *
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): self
    {
        return new self(
            $container->get(RequestDtoMapperInterface::class),
            $container->get('dto.error_handler'),
        );
    }

    /**
     * Wrap a DTO handler to make it PSR-15 compliant
     *
     * @param DtoHandlerInterface $handler The DTO handler to wrap
     * @return RequestHandlerInterface PSR-15 compatible handler
     */
    public function wrap(DtoHandlerInterface $handler): RequestHandlerInterface
    {
        return new DtoHandlerWrapper(
            $handler,
            $this->dtoMapper,
            $this->errorHandler,
        );
    }
}
