<?php

declare(strict_types=1);

namespace Grpcavel\Runtime\DTO;

use Grpcavel\Http\GrpcResponse;

final class HealthCheckResponse extends GrpcResponse
{
    public const UNKNOWN = 0;
    public const SERVING = 1;
    public const NOT_SERVING = 2;
    public const SERVICE_UNKNOWN = 3;

    public function __construct(
        public int $status = self::SERVING,
    ) {}
}
