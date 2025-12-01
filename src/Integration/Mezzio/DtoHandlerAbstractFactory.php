<?php

declare(strict_types=1);

namespace MethorZ\Dto\Integration\Mezzio;

use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use MethorZ\Dto\Factory\DtoHandlerWrapperFactory;
use MethorZ\Dto\Handler\DtoHandlerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Mezzio/Laminas ServiceManager abstract factory for automatic DtoHandlerInterface wrapping
 *
 * This factory automatically:
 * 1. Detects classes implementing DtoHandlerInterface
 * 2. Auto-wires their constructor dependencies from the container
 * 3. Wraps them with DtoHandlerWrapper for PSR-15 compliance
 *
 * NOTE: This is a Mezzio-specific integration using Laminas ServiceManager.
 * For other frameworks, you can manually use DtoHandlerWrapperFactory to wrap your handlers.
 *
 * Usage: Just register this abstract factory in your ConfigProvider:
 * ```php
 * 'abstract_factories' => [
 *     DtoHandlerAbstractFactory::class,
 * ],
 * ```
 *
 * Then handlers work automatically in routes:
 * ```php
 * 'routes' => [
 *     [
 *         'path' => '/api/items',
 *         'middleware' => [CreateItemHandler::class], // Auto-wired + auto-wrapped!
 *     ],
 * ],
 * ```
 */
final class DtoHandlerAbstractFactory implements AbstractFactoryInterface
{
    /**
     * Can this factory create the requested service?
     */
    public function canCreate(ContainerInterface $_container, string $requestedName): bool
    {
        // Only handle classes that exist and implement DtoHandlerInterface
        if (!class_exists($requestedName)) {
            return false;
        }

        return is_subclass_of($requestedName, DtoHandlerInterface::class);
    }

    /**
     * Create and wrap a DtoHandlerInterface implementation
     *
     * @throws \ReflectionException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $_options = null,
    ): RequestHandlerInterface {
        // Use reflection to auto-wire constructor dependencies
        /** @var class-string $requestedName We validate in canCreate() */
        $reflection = new ReflectionClass($requestedName);
        $constructor = $reflection->getConstructor();

        $dependencies = [];
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $param) {
                $type = $param->getType();

                // Only auto-wire typed, non-builtin parameters
                if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                    $typeName = $type->getName();
                    $dependencies[] = $container->get($typeName);
                }
            }
        }

        // Instantiate the handler with auto-wired dependencies
        /** @var DtoHandlerInterface $handler */
        $handler = new $requestedName(...$dependencies);

        // Automatically wrap with DtoHandlerWrapper for PSR-15 compliance
        /** @var DtoHandlerWrapperFactory $factory */
        $factory = $container->get(DtoHandlerWrapperFactory::class);

        return $factory->wrap($handler);
    }
}
