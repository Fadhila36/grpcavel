<?php

declare(strict_types=1);

namespace Grpcavel\Runtime;

use Grpcavel\Attributes\GrpcService;
use Grpcavel\Attributes\GrpcMethod;
use Grpcavel\Runtime\DTO\HealthCheckRequest;
use Grpcavel\Runtime\DTO\HealthCheckResponse;

#[GrpcService(name: 'Health', package: 'grpc.health.v1')]
final class HealthCheckService
{
    #[GrpcMethod(name: 'Check')]
    public function Check(HealthCheckRequest $request): HealthCheckResponse
    {
        try {
            // Check default database connection
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            
            return new HealthCheckResponse(HealthCheckResponse::SERVING);
        } catch (\Throwable $e) {
            // If DB is down, return NOT_SERVING
            return new HealthCheckResponse(HealthCheckResponse::NOT_SERVING);
        }
    }
}
