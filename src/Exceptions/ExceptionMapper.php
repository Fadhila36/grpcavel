<?php

declare(strict_types=1);

namespace Grpcavel\Exceptions;

use Grpcavel\Contracts\ExceptionMapperContract;
use Grpcavel\Http\GrpcStatusResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ExceptionMapper implements ExceptionMapperContract
{
    /**
     * @var array<string, string>
     */
    private array $customMap;

    /**
     * @var array<string, string>
     */
    private const BUILTIN_MAP = [
        ValidationException::class => 'INVALID_ARGUMENT',
        AuthenticationException::class => 'UNAUTHENTICATED',
        AuthorizationException::class => 'PERMISSION_DENIED',
        ModelNotFoundException::class => 'NOT_FOUND',
    ];

    /**
     * @param  array<string, string>  $customMap
     */
    public function __construct(array $customMap = [])
    {
        $this->customMap = $customMap;
    }

    /**
     * Map a throwable to a gRPC status response.
     *
     * @param  Throwable  $e
     * @return GrpcStatusResponse
     */
    public function map(Throwable $e): GrpcStatusResponse
    {
        // 1. Check custom map first
        foreach ($this->customMap as $exceptionClass => $statusCode) {
            if ($e instanceof $exceptionClass) {
                return new GrpcStatusResponse($statusCode, $e->getMessage());
            }
        }

        // 2. Check built-in map
        foreach (self::BUILTIN_MAP as $exceptionClass => $statusCode) {
            if ($e instanceof $exceptionClass) {
                // ValidationException special handling to include error messages
                if ($e instanceof ValidationException) {
                    return new GrpcStatusResponse($statusCode, $this->formatValidationErrors($e));
                }
                
                return new GrpcStatusResponse($statusCode, $e->getMessage());
            }
        }

        // 3. Default to INTERNAL
        Log::error($e->getMessage(), [
            'exception' => get_class($e),
            'trace' => $e->getTraceAsString(),
        ]);

        return new GrpcStatusResponse(
            'INTERNAL',
            'An internal error occurred.'
        );
    }

    /**
     * Register a custom exception mapping.
     *
     * @param  string  $exceptionClass
     * @param  string  $statusCode
     */
    public function register(string $exceptionClass, string $statusCode): void
    {
        // Prepend to give it priority
        $this->customMap = [$exceptionClass => $statusCode] + $this->customMap;
    }

    /**
     * Format validation errors into a string.
     */
    private function formatValidationErrors(ValidationException $e): string
    {
        $errors = $e->validator->errors()->all();
        
        return implode(' ', $errors);
    }
}
