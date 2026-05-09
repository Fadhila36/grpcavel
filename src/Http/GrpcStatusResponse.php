<?php

declare(strict_types=1);

namespace Grpcavel\Http;

/**
 * Represents a gRPC status response returned when an error occurs during handler execution.
 *
 * This is an internal value object produced by the ExceptionMapper and returned
 * to the RoadRunner runtime as the final response when a handler throws an exception.
 */
final readonly class GrpcStatusResponse
{
    /**
     * @param  string               $statusCode  The gRPC status code string (e.g. 'INVALID_ARGUMENT')
     * @param  string               $message     A human-readable description of the status
     * @param  array<string, mixed> $details     Optional structured details about the error
     */
    public function __construct(
        public string $statusCode,
        public string $message,
        /** @var array<string, mixed> */
        public array $details = [],
    ) {}
}
