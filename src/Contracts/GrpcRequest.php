<?php

declare(strict_types=1);

namespace Grpcavel\Contracts;

/**
 * Interface for gRPC Request DTOs that support validation.
 */
interface GrpcRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array;

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array;

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array;
}
