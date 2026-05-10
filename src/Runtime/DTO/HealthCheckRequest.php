<?php

declare(strict_types=1);

namespace Grpcavel\Runtime\DTO;

use Grpcavel\Http\GrpcRequest;

final class HealthCheckRequest extends GrpcRequest
{
    public function __construct(
        public string $service = '',
    ) {}

    public function rules(): array
    {
        return [
            'service' => 'nullable|string',
        ];
    }
}
