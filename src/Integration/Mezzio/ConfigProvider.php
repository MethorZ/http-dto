<?php

declare(strict_types=1);

namespace MethorZ\Dto\Integration\Mezzio;

use MethorZ\Dto\Factory\DtoHandlerWrapperFactory;
use MethorZ\Dto\Middleware\AutoJsonResponseMiddleware;
use MethorZ\Dto\RequestDtoMapper;
use MethorZ\Dto\RequestDtoMapperInterface;
use MethorZ\Dto\Response\JsonResponseFactory;
use MethorZ\Dto\Validator\SymfonyValidatorAdapter;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Mezzio configuration provider for the http-dto package
 *
 * Registers the automatic DTO handler wrapping via abstract factory.
 *
 * NOTE: This is a Mezzio-specific integration. For other frameworks,
 * you can manually configure the RequestDtoMapper and DtoHandlerWrapperFactory.
 *
 * Requirements:
 * - ResponseFactoryInterface and StreamFactoryInterface must be registered in the container
 *   (typically provided by laminas-diactoros, nyholm/psr7, or any PSR-17 implementation)
 *
 * Usage in config/config.php:
 * ```php
 * $aggregator = new ConfigAggregator([
 *     MethorZ\Dto\Integration\Mezzio\ConfigProvider::class,
 *     // ... other providers
 * ]);
 * ```
 *
 * Then define a custom error handler in your Base ConfigProvider:
 * ```php
 * 'services' => [
 *     'dto.error_handler' => function ($exception) {
 *         // Return PSR-7 ResponseInterface
 *         return ApiResponse::validationError($exception->getErrors());
 *     },
 * ],
 * ```
 */
final class ConfigProvider
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDependencies(): array
    {
        return [
            'aliases' => [
                // Default DTO mapper implementation
                RequestDtoMapperInterface::class => RequestDtoMapper::class,
            ],
            'factories' => [
                // DTO mapper with Symfony Validator adapter
                RequestDtoMapper::class => static function (
                    ContainerInterface $container,
                ): RequestDtoMapper {
                    /** @var ValidatorInterface $symfonyValidator */
                    $symfonyValidator = $container->get(ValidatorInterface::class);
                    $validator = new SymfonyValidatorAdapter($symfonyValidator);

                    return new RequestDtoMapper($validator);
                },

                // JSON response factory (framework-agnostic via PSR-17)
                JsonResponseFactory::class => static function (ContainerInterface $container): JsonResponseFactory {
                    /** @var ResponseFactoryInterface $responseFactory */
                    $responseFactory = $container->get(ResponseFactoryInterface::class);
                    /** @var StreamFactoryInterface $streamFactory */
                    $streamFactory = $container->get(StreamFactoryInterface::class);

                    return new JsonResponseFactory($responseFactory, $streamFactory);
                },

                // Wrapper factory needs mapper, JSON factory, and error handler
                DtoHandlerWrapperFactory::class => static function (
                    ContainerInterface $container,
                ): DtoHandlerWrapperFactory {
                    /** @var RequestDtoMapperInterface $dtoMapper */
                    $dtoMapper = $container->get(RequestDtoMapperInterface::class);
                    /** @var JsonResponseFactory $jsonResponseFactory */
                    $jsonResponseFactory = $container->get(JsonResponseFactory::class);
                    /** @var callable $errorHandler */
                    $errorHandler = $container->get('dto.error_handler');

                    return new DtoHandlerWrapperFactory(
                        $dtoMapper,
                        $jsonResponseFactory,
                        $errorHandler,
                    );
                },

                // Auto JSON response middleware
                AutoJsonResponseMiddleware::class => static function (
                    ContainerInterface $container,
                ): AutoJsonResponseMiddleware {
                    /** @var JsonResponseFactory $jsonResponseFactory */
                    $jsonResponseFactory = $container->get(JsonResponseFactory::class);

                    return new AutoJsonResponseMiddleware($jsonResponseFactory);
                },
            ],
            'abstract_factories' => [
                // ✨ Magic! Automatically wraps all DtoHandlerInterface implementations
                DtoHandlerAbstractFactory::class,
            ],
        ];
    }
}
