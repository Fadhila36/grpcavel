<?php

declare(strict_types=1);

namespace Grpcavel\Discovery;

use Grpcavel\Attributes\GrpcMethod;
use Grpcavel\Attributes\GrpcService;
use Grpcavel\Attributes\Middleware;
use Grpcavel\Contracts\ServiceDiscovererContract;
use Grpcavel\Runtime\ServiceRegistry;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use SplFileInfo;

final class ServiceDiscoverer implements ServiceDiscovererContract
{
    public function __construct(
        private readonly string $servicesPath,
        private readonly ?string $cachePath = null,
    ) {}

    public function discover(): array
    {
        if ($this->cachePath !== null && file_exists($this->cachePath)) {
            return require $this->cachePath;
        }

        if (! is_dir($this->servicesPath)) {
            return [];
        }

        $this->loadPhpFiles($this->servicesPath);

        $services = [];

        foreach (get_declared_classes() as $className) {
            if (! $this->isInServicesPath($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            if (! $reflection->isInstantiable()) {
                continue;
            }

            $serviceAttributes = $reflection->getAttributes(GrpcService::class);
            if ($serviceAttributes === []) {
                continue;
            }

            /** @var GrpcService $serviceAttr */
            $serviceAttr = $serviceAttributes[0]->newInstance();

            $services[] = new ServiceDefinition(
                className: $className,
                serviceName: $this->deriveServiceName($reflection->getShortName(), $serviceAttr->name),
                package: $serviceAttr->package ?? '',
                handlers: $this->discoverHandlers($reflection),
                middlewareClasses: $this->extractMiddlewareClasses($reflection->getAttributes(Middleware::class)),
            );
        }

        return $services;
    }

    public function register(ServiceRegistry $registry): void
    {
        foreach ($this->discover() as $definition) {
            $registry->register($definition);
        }
    }

    private function loadPhpFiles(string $directory): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                require_once $file->getRealPath();
            }
        }
    }

    private function isInServicesPath(string $className): bool
    {
        try {
            $reflection = new ReflectionClass($className);
            $fileName = $reflection->getFileName();

            if ($fileName === false) {
                return false;
            }

            $realServicesPath = realpath($this->servicesPath);
            if ($realServicesPath === false) {
                return false;
            }

            return str_starts_with(
                str_replace('\\', '/', $fileName),
                str_replace('\\', '/', $realServicesPath),
            );
        } catch (\ReflectionException) {
            return false;
        }
    }

    private function deriveServiceName(string $shortName, ?string $explicitName): string
    {
        if ($explicitName !== null && $explicitName !== '') {
            return $explicitName;
        }

        if (str_ends_with($shortName, 'Service')) {
            return substr($shortName, 0, -strlen('Service'));
        }

        return $shortName;
    }

    /**
     * @return array<HandlerDefinition>
     */
    private function discoverHandlers(ReflectionClass $reflection): array
    {
        $handlers = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (! $this->qualifiesAsHandler($method, $reflection)) {
                continue;
            }

            $params = $method->getParameters();
            /** @var ReflectionNamedType $paramType */
            $paramType = $params[0]->getType();
            /** @var ReflectionNamedType $returnType */
            $returnType = $method->getReturnType();

            $handlers[] = new HandlerDefinition(
                methodName: $method->getName(),
                rpcName: $this->deriveRpcName($method),
                requestClass: $paramType->getName(),
                responseClass: $returnType->getName(),
                middlewareClasses: $this->extractMiddlewareClasses($method->getAttributes(Middleware::class)),
            );
        }

        return $handlers;
    }

    private function qualifiesAsHandler(ReflectionMethod $method, ReflectionClass $serviceReflection): bool
    {
        if ($method->getDeclaringClass()->getName() !== $serviceReflection->getName()) {
            return false;
        }

        if (str_starts_with($method->getName(), '__')) {
            return false;
        }

        $params = $method->getParameters();
        if (count($params) !== 1) {
            return false;
        }

        $paramType = $params[0]->getType();
        if (! ($paramType instanceof ReflectionNamedType) || $paramType->isBuiltin()) {
            return false;
        }

        $returnType = $method->getReturnType();
        if (! ($returnType instanceof ReflectionNamedType) || $returnType->isBuiltin()) {
            return false;
        }

        return true;
    }

    private function deriveRpcName(ReflectionMethod $method): string
    {
        $attributes = $method->getAttributes(GrpcMethod::class);
        
        if ($attributes !== []) {
            /** @var GrpcMethod $instance */
            $instance = $attributes[0]->newInstance();
            if ($instance->name !== null && $instance->name !== '') {
                return $instance->name;
            }
        }

        return $method->getName();
    }

    /**
     * @param  array<\ReflectionAttribute<Middleware>>  $attributes
     * @return array<string>
     */
    private function extractMiddlewareClasses(array $attributes): array
    {
        return array_map(function ($attribute) {
            /** @var Middleware $instance */
            $instance = $attribute->newInstance();
            return $instance->middlewareClass;
        }, $attributes);
    }
}
