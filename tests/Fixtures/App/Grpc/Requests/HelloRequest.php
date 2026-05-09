<?php

declare(strict_types=1);

namespace Grpcavel\Tests\Fixtures\App\Grpc\Requests;

use Grpcavel\Http\GrpcRequest;

final class HelloRequest extends GrpcRequest
{
    public function __construct(
        public readonly string $name,
    ) {}

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }
}
