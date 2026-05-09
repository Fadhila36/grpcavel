<?php

declare(strict_types=1);

use Grpcavel\Contracts\SerializerContract;
use Illuminate\Database\Eloquent\Model;

if (! function_exists('grpc')) {
    /**
     * Map an Eloquent model to a gRPC response DTO.
     *
     * @template T of object
     * @param  Model          $model
     * @param  class-string<T> $responseClass
     * @return T
     */
    function grpc(Model $model, string $responseClass): object
    {
        return app(SerializerContract::class)->fromModel($model, $responseClass);
    }
}
