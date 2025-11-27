<?php

declare(strict_types=1);

namespace MethorZ\Dto;

use MethorZ\Dto\Cache\ReflectionCache;
use MethorZ\Dto\Caster\CasterRegistry;
use MethorZ\Dto\Caster\CollectionCaster;
use MethorZ\Dto\Caster\DateTimeCaster;
use MethorZ\Dto\Caster\DtoCaster;
use MethorZ\Dto\Caster\EnumCaster;
use MethorZ\Dto\Caster\ScalarCaster;
use MethorZ\Dto\Caster\UuidCaster;
use MethorZ\Dto\Exception\MappingException;
use MethorZ\Dto\Exception\ValidationException;
use MethorZ\Dto\Validator\NullValidator;
use MethorZ\Dto\Validator\ValidatorInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use ReflectionParameter;
use Throwable;

use function array_key_exists;
use function is_array;

final readonly class RequestDtoMapper implements RequestDtoMapperInterface
{
    private CasterRegistry $casterRegistry;
    private ReflectionCache $reflectionCache;
    private ValidatorInterface $validator;

    public function __construct(
        ?ValidatorInterface $validator = null,
        ?CasterRegistry $casterRegistry = null,
        ?ReflectionCache $reflectionCache = null,
    ) {
        $this->validator = $validator ?? new NullValidator();
        $this->casterRegistry = $casterRegistry ?? $this->createDefaultCasterRegistry();
        $this->reflectionCache = $reflectionCache ?? new ReflectionCache();
    }

    /**
     * Create default caster registry with all built-in casters
     */
    private function createDefaultCasterRegistry(): CasterRegistry
    {
        $registry = new CasterRegistry();

        // Register built-in casters in priority order
        $registry->register(new ScalarCaster());
        $registry->register(new DateTimeCaster());
        $registry->register(new UuidCaster());
        $registry->register(new EnumCaster());

        // DtoCaster needs registry to handle nested structures via CollectionCaster
        $dtoCaster = new DtoCaster($registry);
        $registry->register($dtoCaster);

        // CollectionCaster needs both registry AND dtoCaster for proper nested DTO handling
        $collectionCaster = new CollectionCaster($registry, $dtoCaster);
        $registry->register($collectionCaster);

        return $registry;
    }

    /**
     * @template T of object
     * @param class-string<T> $dtoClass
     * @return T
     * @throws MappingException
     * @throws ValidationException
     */
    public function map(string $dtoClass, ServerRequestInterface $request): object
    {
        try {
            // Use cached reflection for performance (~87% faster for cached DTOs)
            $reflection = $this->reflectionCache->getReflection($dtoClass);
            $constructor = $reflection->getConstructor();
            if ($constructor === null) {
                throw MappingException::noConstructor($dtoClass);
            }

            // Get data from request (parsed body + route attributes)
            $requestData = $this->extractRequestData($request);

            // Get cached constructor parameters for performance
            $parameters = $this->reflectionCache->getConstructorParameters($dtoClass);

            // Map constructor parameters
            $constructorArgs = $this->mapConstructorParameters(
                $parameters,
                $requestData,
                $dtoClass,
            );

            // Instantiate DTO
            $dto = $reflection->newInstanceArgs($constructorArgs);
        } catch (ReflectionException $e) {
            throw MappingException::instantiationFailed($dtoClass, $e);
        } catch (Throwable $e) {
            throw MappingException::instantiationFailed($dtoClass, $e);
        }

        // Validate DTO (validator throws ValidationException if validation fails)
        $this->validator->validate($dto);

        return $dto;
    }

    /**
     * Extract data from request body, query parameters, and route attributes
     *
     * Priority order (highest to lowest):
     * 1. Route attributes (e.g., {id} from /items/{id})
     * 2. Request body (POST/PUT JSON or form data)
     * 3. Query parameters (e.g., ?limit=10&offset=0)
     *
     * @return array<string, mixed>
     */
    private function extractRequestData(ServerRequestInterface $request): array
    {
        $data = [];

        // Start with query parameters (lowest priority)
        $queryParams = $request->getQueryParams();
        if (is_array($queryParams)) {
            $data = $queryParams;
        }

        // Get parsed body (POST/PUT data) - higher priority than query params
        $body = $request->getParsedBody();

        if (is_array($body) && $body !== []) {
            // Body already parsed and contains data (form data or upstream middleware)
            $data = array_merge($data, $body);
        } else {
            // PSR-7 doesn't auto-parse JSON - do it manually if Content-Type is JSON
            $contentType = $request->getHeaderLine('Content-Type');

            if (strpos($contentType, 'application/json') !== false) {
                // Get raw body as string
                $bodyStream = $request->getBody();

                // Try to rewind if seekable
                try {
                    if ($bodyStream->isSeekable()) {
                        $bodyStream->rewind();
                    }
                } catch (\Exception $e) {
                    // Ignore rewind failures - stream might not be seekable
                }

                $rawBody = $bodyStream->__toString();

                if ($rawBody !== '' && $rawBody !== '{}' && $rawBody !== 'null') {
                    $jsonData = json_decode($rawBody, true);
                    if (is_array($jsonData) && $jsonData !== []) {
                        $data = array_merge($data, $jsonData);
                    }
                }
            }
        }

        // Merge route attributes (highest priority - e.g., {id} from route)
        $attributes = $request->getAttributes();
        foreach ($attributes as $key => $value) {
            // Skip internal PSR-7 attributes
            if ($key === 'request-target' || $key === 'middleware-matched') {
                continue;
            }
            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * Map constructor parameters from request data
     *
     * @param array<ReflectionParameter> $parameters
     * @param array<string, mixed> $requestData
     * @return array<int, mixed>
     * @throws MappingException
     * @throws \ReflectionException
     */
    private function mapConstructorParameters(
        array $parameters,
        array $requestData,
        string $dtoClass,
    ): array {
        $args = [];

        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();

            // Check if value exists in request data
            if (array_key_exists($paramName, $requestData)) {
                $args[] = $this->castParameterValue(
                    $requestData[$paramName],
                    $parameter,
                );
                continue;
            }

            // Use default value if available
            if ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
                continue;
            }

            // Parameter is required but missing
            throw MappingException::missingRequiredParameter(
                $dtoClass,
                $paramName,
            );
        }

        return $args;
    }

    /**
     * Cast request value to match parameter type using caster registry
     *
     * @throws \MethorZ\Dto\Caster\CastException
     */
    private function castParameterValue(mixed $value, ReflectionParameter $parameter): mixed
    {
        // Resolve appropriate caster
        $caster = $this->casterRegistry->resolve($parameter);

        // No caster found - return value as-is
        if ($caster === null) {
            return $value;
        }

        // Cast using resolved caster
        return $caster->cast($value, $parameter);
    }
}
