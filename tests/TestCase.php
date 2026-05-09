<?php

declare(strict_types=1);

namespace Grpcavel\Tests;

use Grpcavel\GrpcavelServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            GrpcavelServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default config for tests
        $app['config']->set('grpc.services_path', app_path('Grpc/Services'));
        $app['config']->set('grpc.proto_path', base_path('proto'));
        $app['config']->set('grpc.generated_path', app_path('Grpc/Generated'));
        $app['config']->set('grpc.cache_path', storage_path('grpc_cache.php'));
    }
}
