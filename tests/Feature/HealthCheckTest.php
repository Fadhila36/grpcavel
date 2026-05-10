<?php

declare(strict_types=1);

use Grpcavel\Testing\GrpcClient;
use Grpcavel\Runtime\HealthCheckService;
use Grpcavel\Runtime\DTO\HealthCheckRequest;
use Grpcavel\Runtime\DTO\HealthCheckResponse;

it('can call health check service and returns serving', function () {
    /** @var HealthCheckResponse $response */
    $response = GrpcClient::call(
        HealthCheckService::class,
        'Check',
        new HealthCheckRequest(service: '')
    );

    expect($response)->toBeInstanceOf(HealthCheckResponse::class);
    expect($response->status)->toBe(HealthCheckResponse::SERVING);
});
