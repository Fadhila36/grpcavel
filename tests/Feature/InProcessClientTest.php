<?php

declare(strict_types=1);

use Grpcavel\Testing\GrpcClient;
use Grpcavel\Tests\Fixtures\App\Grpc\Requests\HelloRequest;
use Grpcavel\Tests\Fixtures\App\Grpc\Responses\HelloResponse;
use Grpcavel\Tests\Fixtures\App\Grpc\Services\HelloService;

it('can call a service in-process using the grpc client', function () {
    // Override the services path to point to our fixtures
    config(['grpc.services_path' => __DIR__ . '/../Fixtures/App/Grpc/Services']);
    
    // Force discovery to run again if needed, or we just rely on the test booting
    $app = app();
    $discoverer = $app->make(\Grpcavel\Contracts\ServiceDiscovererContract::class);
    $registry = $app->make(\Grpcavel\Runtime\ServiceRegistry::class);
    $discoverer->register($registry);

    $name = 'Fadhila';
    
    /** @var HelloResponse $response */
    $response = GrpcClient::call(
        HelloService::class,
        'sayHello',
        new HelloRequest(name: $name)
    );

    expect($response)->toBeInstanceOf(HelloResponse::class);
    expect($response->message)->toBe("Hello $name");
});
