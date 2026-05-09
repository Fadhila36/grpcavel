<?php

declare(strict_types=1);

namespace Grpcavel\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Contract for mapping Eloquent models to gRPC response DTOs.
 */
interface SerializerContract
{
    /**
     * Map an Eloquent model's attributes to a response DTO instance.
     *
     * @template T of object
     * @param  Model          $model          The Eloquent model to serialize
     * @param  class-string<T> $responseClass  The target response DTO class
     * @return T
     */
    public function fromModel(Model $model, string $responseClass): object;

    /**
     * Serialize the given DTO into an associative array for the gRPC response.
     *
     * @param  object  $dto
     * @return array<string, mixed>
     */
    public function serialize(object $dto): array;
}
