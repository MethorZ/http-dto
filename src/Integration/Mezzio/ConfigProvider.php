<?php

declare(strict_types=1);

namespace MethorZ\Dto\Integration\Mezzio;

use MethorZ\Dto\Exception\MappingException;
use MethorZ\Dto\Exception\ValidationException;
use MethorZ\Dto\Factory\DtoHandlerWrapperFactory;
use MethorZ\Dto\Middleware\AutoJsonResponseMiddleware;
use MethorZ\Dto\RequestDtoMapper;
use MethorZ\Dto\RequestDtoMapperInterface;
use MethorZ\Dto\Response\JsonResponseFactory;
use MethorZ\Dto\Validator\NullValidator;
use MethorZ\Dto\Validator\SymfonyValidatorAdapter;
use MethorZ\Dto\Validator\ValidatorInterface as DtoValidatorInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;

/**
 * Mezzio configuration provider for the http-dto package
 *
 * Zero-config integration - just add to your ConfigAggregator and it works!
 *
 * Provides:
 * - Automatic DTO handler wrapping via abstract factory
 * - Automatic validation (if symfony/validator is installed)
 * - Default error handler for validation/mapping errors
 *
 * Usage in config/config.php:
 * ```php
 * $aggregator = new ConfigAggregator([
 *     MethorZ\Dto\Integration\Mezzio\ConfigProvider::class,
 *     // ... other providers
 * ]);
 * ```
 *
 * Customization (optional):
 * Override 'dto.error_handler' in your ConfigProvider to customize error responses:
 * ```php
 * 'factories' => [
 *     'dto.error_handler' => fn() => function ($exception) {
 *         return new JsonResponse(['error' => $exception->getMessage()], 400);
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
                // Symfony Validator - auto-configured if symfony/validator is installed
                SymfonyValidatorInterface::class => static function (): SymfonyValidatorInterface {
                    return Validation::createValidatorBuilder()
                        ->enableAttributeMapping()
                        ->getValidator();
                },

                // DTO Validator - uses Symfony Validator if available, otherwise NullValidator
                DtoValidatorInterface::class => static function (
                    ContainerInterface $container,
                ): DtoValidatorInterface {
                    // Use Symfony Validator if available
                    if ($container->has(SymfonyValidatorInterface::class)) {
                        /** @var SymfonyValidatorInterface $symfonyValidator */
                        $symfonyValidator = $container->get(SymfonyValidatorInterface::class);

                        return new SymfonyValidatorAdapter($symfonyValidator);
                    }

                    // Fallback to NullValidator (no validation)
                    return new NullValidator();
                },

                // DTO mapper with validator
                RequestDtoMapper::class => static function (
                    ContainerInterface $container,
                ): RequestDtoMapper {
                    /** @var DtoValidatorInterface $validator */
                    $validator = $container->get(DtoValidatorInterface::class);

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

                // Default error handler - returns RFC 7807-style JSON error response
                'dto.error_handler' => static function (
                    ContainerInterface $container,
                ): callable {
                    /** @var JsonResponseFactory $jsonResponseFactory */
                    $jsonResponseFactory = $container->get(JsonResponseFactory::class);

                    return static function (
                        ValidationException|MappingException $exception,
                    ) use ($jsonResponseFactory): ResponseInterface {
                        $data = [
                            'type' => 'about:blank',
                            'title' => $exception instanceof ValidationException
                                ? 'Validation Failed'
                                : 'Bad Request',
                            'status' => 400,
                            'detail' => $exception->getMessage(),
                        ];

                        // Add validation errors if available
                        if ($exception instanceof ValidationException) {
                            $data['errors'] = $exception->getErrors();
                        }

                        return $jsonResponseFactory->create($data, 400);
                    };
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
                // Automatically wraps all DtoHandlerInterface implementations
                DtoHandlerAbstractFactory::class,
            ],
        ];
    }
}
