<?php

declare(strict_types=1);

namespace Grpcavel\Tests\Fixtures\App\Grpc\Responses;

use Grpcavel\Http\GrpcResponse;

final class HelloResponse extends GrpcResponse
{
    public function __construct(
        public readonly string $message = '',
    ) {}
}
