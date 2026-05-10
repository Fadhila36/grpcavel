<?php

declare(strict_types=1);

namespace Grpcavel\Testing;

use Grpcavel\GrpcavelServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class GrpcTestCase extends OrchestraTestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            GrpcavelServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        /** @var \Illuminate\Contracts\Config\Repository $config */
        $config = $app['config'];

        $config->set('grpc.services_path', base_path('app/Grpc/Services'));
        $config->set('grpc.proto_path', base_path('proto'));
        $config->set('grpc.generated_path', base_path('app/Grpc/Generated'));
    }
}
