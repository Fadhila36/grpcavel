<?php

declare(strict_types=1);

namespace Grpcavel\Http;

use Grpcavel\Contracts\GrpcRequest as GrpcRequestContract;

/**
 * Base class for gRPC request DTOs.
 *
 * Extend this class in your request DTOs to enable Laravel-style validation.
 * Override {@see rules()} to define validation rules and {@see messages()} to
 * provide custom error messages.
 */
abstract class GrpcRequest implements GrpcRequestContract
{
    /**
     * Return Laravel validation rules for this request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Return custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Return custom attribute names.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [];
    }
}
