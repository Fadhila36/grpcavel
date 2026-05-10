<?php

declare(strict_types=1);

namespace Grpcavel\Middleware;

use Closure;
use Grpcavel\Runtime\GrpcStatus;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Request;

final class RateLimitMiddleware
{
    public function __construct(
        private readonly RateLimiter $limiter
    ) {}

    /**
     * Handle an incoming gRPC request.
     *
     * @param  object   $request
     * @param  Closure  $next
     * @return object
     */
    public function handle(object $request, Closure $next): object
    {
        // Use client IP or a custom identifier if available in context
        // In gRPC context, we might need to extract the IP from RoadRunner metadata
        // For now, we'll use a generic fallback or the IP if it's available via Request facade
        $key = Request::ip() ?? 'global';
        
        $maxAttempts = config('grpc.rate_limit.max_attempts', 60);
        $maxAttempts = is_int($maxAttempts) ? $maxAttempts : 60;
        
        $decayMinutes = config('grpc.rate_limit.decay_minutes', 1);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            throw new \RuntimeException(
                'Rate limit exceeded. Please try again in ' . $this->limiter->availableIn($key) . ' seconds.',
                GrpcStatus::RESOURCE_EXHAUSTED
            );
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        return $next($request);
    }
}
