<?php

declare(strict_types=1);

namespace Grpcavel;

use Grpcavel\Commands\CompileCommand;
use Grpcavel\Commands\InstallCommand;
use Grpcavel\Commands\MakeRequestCommand;
use Grpcavel\Commands\MakeResponseCommand;
use Grpcavel\Commands\MakeServiceCommand;
use Grpcavel\Commands\MakeClientCommand;
use Grpcavel\Commands\StartCommand;
use Grpcavel\Commands\SyncCommand;
use Grpcavel\Commands\WorkerCommand;
use Grpcavel\Commands\CacheCommand;
use Grpcavel\Commands\ClearCacheCommand;
use Grpcavel\Commands\KubernetesCommand;
use Grpcavel\Contracts\ExceptionMapperContract;
use Grpcavel\Contracts\MiddlewarePipelineContract;
use Grpcavel\Contracts\ProtoCompilerContract;
use Grpcavel\Contracts\SerializerContract;
use Grpcavel\Contracts\ServiceDiscovererContract;
use Grpcavel\Contracts\ValidatorContract;
use Grpcavel\Exceptions\ExceptionMapper;
use Grpcavel\Middleware\MiddlewarePipeline;
use Grpcavel\Proto\ProtoCompiler;
use Grpcavel\Proto\ProtoWriter;
use Grpcavel\Proto\TypeMapper;
use Grpcavel\Contracts\RequestDispatcherContract;
use Grpcavel\Runtime\RequestDispatcher;
use Grpcavel\Runtime\ServiceRegistry;
use Grpcavel\Runtime\Worker;
use Grpcavel\Serialization\ModelSerializer;
use Grpcavel\Validation\RequestValidator;
use Grpcavel\Discovery\ServiceDiscoverer;
use Grpcavel\Discovery\ServiceDefinition;
use Grpcavel\Discovery\HandlerDefinition;
use Grpcavel\Runtime\HealthCheckService;
use Grpcavel\Runtime\DTO\HealthCheckRequest;
use Grpcavel\Runtime\DTO\HealthCheckResponse;
use Illuminate\Support\ServiceProvider;

final class GrpcavelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ServiceDiscovererContract::class, function ($app) {
            $servicesPath = config('grpc.services_path', app_path('Grpc/Services'));
            $cachePath = config('grpc.cache_path');

            return new ServiceDiscoverer(
                servicesPath: is_string($servicesPath) ? $servicesPath : app_path('Grpc/Services'),
                cachePath: is_string($cachePath) ? $cachePath : null,
            );
        });
        $this->app->bind(ProtoCompilerContract::class, function ($app) {
            $protoPath = config('grpc.proto_path', base_path('proto'));

            return new ProtoCompiler(
                typeMapper: $app->make(TypeMapper::class),
                protoWriter: $app->make(ProtoWriter::class),
                protoPath: is_string($protoPath) ? $protoPath : base_path('proto'),
            );
        });
        $this->app->bind(MiddlewarePipelineContract::class, MiddlewarePipeline::class);
        $this->app->bind(ExceptionMapperContract::class, function ($app) {
            /** @var array<string, string> $customMap */
            $customMap = config('grpc.exception_map', []);

            return new ExceptionMapper(
                customMap: $customMap
            );
        });
        $this->app->singleton(RequestDispatcherContract::class, RequestDispatcher::class);
        $this->app->singleton(ServiceRegistry::class, ServiceRegistry::class);

        $this->app->bind(Worker::class, function ($app) {
            return new Worker(
                registry: $app->make(ServiceRegistry::class),
                dispatcher: $app->make(RequestDispatcherContract::class),
            );
        });
        $this->app->bind(SerializerContract::class, ModelSerializer::class);
        $this->app->bind(ValidatorContract::class, RequestValidator::class);
    }

    public function boot(): void
    {
        $this->publishes(
            [__DIR__ . '/../config/grpc.php' => config_path('grpc.php')],
            'grpc-config',
        );

        // Discover and register services into the registry
        $this->app->make(ServiceDiscovererContract::class)
            ->register($this->app->make(ServiceRegistry::class));

        // Register native health check service
        /** @var ServiceRegistry $registry */
        $registry = $this->app->make(ServiceRegistry::class);
        $registry->register(new ServiceDefinition(
            className: HealthCheckService::class,
            serviceName: 'Health',
            package: 'grpc.health.v1',
            handlers: [
                new HandlerDefinition(
                    methodName: 'Check',
                    rpcName: 'Check',
                    requestClass: HealthCheckRequest::class,
                    responseClass: HealthCheckResponse::class,
                    middlewareClasses: []
                )
            ],
            middlewareClasses: []
        ));

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                SyncCommand::class,
                CompileCommand::class,
                StartCommand::class,
                WorkerCommand::class,
                MakeServiceCommand::class,
                MakeRequestCommand::class,
                MakeResponseCommand::class,
                MakeClientCommand::class,
                CacheCommand::class,
                ClearCacheCommand::class,
                KubernetesCommand::class,
            ]);
        }
    }
}
