<?php

declare(strict_types=1);

namespace Grpcavel\Tests\Fixtures\App\Grpc\Services;

use Grpcavel\Attributes\GrpcService;
use Grpcavel\Attributes\GrpcMethod;
use Grpcavel\Tests\Fixtures\App\Grpc\Requests\HelloRequest;
use Grpcavel\Tests\Fixtures\App\Grpc\Responses\HelloResponse;

#[GrpcService(name: 'Hello')]
final class HelloService
{
    #[GrpcMethod]
    public function sayHello(HelloRequest $request): HelloResponse
    {
        return new HelloResponse(
            message: 'Hello ' . $request->name
        );
    }
}
