<?php

declare(strict_types=1);

namespace Grpcavel\Http;

use Grpcavel\Contracts\SerializerContract;
use Illuminate\Database\Eloquent\Model;

/**
 * Base class for gRPC response DTOs.
 *
 * Extend this class in your response DTOs to gain the {@see fromModel()} helper,
 * which maps Eloquent model attributes to DTO properties automatically.
 */
abstract class GrpcResponse
{
    /**
     * Populate a new instance from an Eloquent model by matching attribute names to properties.
     *
     * @param  Model  $model  The Eloquent model to serialize into this DTO
     * @return static
     */
    public static function fromModel(Model $model): static
    {
        return app(SerializerContract::class)->fromModel($model, static::class);
    }
}
