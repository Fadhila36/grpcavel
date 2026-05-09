<?php

declare(strict_types=1);

namespace Grpcavel\Contracts;

use Grpcavel\Http\GrpcStatusResponse;

/**
 * Contract for mapping PHP exceptions to gRPC status responses.
 */
interface ExceptionMapperContract
{
    /**
     * Map a Throwable to a gRPC status response.
     */
    public function map(\Throwable $e): GrpcStatusResponse;

    /**
     * Register a custom exception class → gRPC status code mapping.
     *
     * @param  string  $exceptionClass  Fully-qualified exception class name
     * @param  string  $statusCode      gRPC status code string (e.g. 'NOT_FOUND')
     */
    public function register(string $exceptionClass, string $statusCode): void;
}
