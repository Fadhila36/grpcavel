<?php

declare(strict_types=1);

return [

    'server' => [
        /**
         * The host address RoadRunner listens on for gRPC connections.
         * Accepts any valid IP address or hostname.
         * Default: '0.0.0.0' (all interfaces).
         */
        'host' => env('GRPC_HOST', '0.0.0.0'),

        /**
         * The port RoadRunner listens on for gRPC connections.
         * Must be an integer between 1 and 65535.
         * Default: 9001.
         */
        'port' => (int) env('GRPC_PORT', 9001),
    ],

    /**
     * Absolute path to the directory scanned recursively for #[GrpcService]-annotated classes.
     * The Service_Discoverer will register every class found here that carries the attribute.
     * Default: app/Grpc/Services (resolved via app_path()).
     */
    'services_path' => env('GRPC_SERVICES_PATH', app_path('Grpc/Services')),

    /**
     * Absolute path to the file used to cache discovered service definitions.
     * When this file exists, the framework skips directory scanning and reflection.
     * Default: bootstrap/cache/grpc.php (resolved via base_path()).
     */
    'cache_path' => base_path('bootstrap/cache/grpc.php'),

    /**
     * Absolute path where generated .proto files are written by grpc:sync.
     * The directory will be created automatically if it does not exist.
     * Default: proto/ in the project root (resolved via base_path()).
     */
    'proto_path' => env('GRPC_PROTO_PATH', base_path('proto')),

    /**
     * Absolute path where protoc-generated PHP stubs are placed by grpc:compile.
     * The directory will be created automatically if it does not exist.
     * Default: app/Grpc/Generated (resolved via app_path()).
     */
    'generated_path' => env('GRPC_GENERATED_PATH', app_path('Grpc/Generated')),

    /**
     * Global middleware applied to every handler across all services.
     * Executed before class-level and method-level middleware.
     * Each entry must be a fully-qualified class name implementing the middleware contract.
     * Example: [\App\Grpc\Middleware\AuthMiddleware::class]
     * Default: [] (no global middleware).
     *
     * @var array<int, class-string>
     */
    'middleware' => [],

    /**
     * Custom exception class → gRPC status code string mappings.
     * These are checked before (and can override) the built-in mappings.
     * Keys are fully-qualified exception class names; values are gRPC status code strings
     * (e.g., 'NOT_FOUND', 'INVALID_ARGUMENT', 'PERMISSION_DENIED').
     * Example: [\App\Exceptions\PaymentException::class => 'FAILED_PRECONDITION']
     * Default: [] (only built-in mappings apply).
     *
     * @var array<class-string, string>
     */
    'exception_map' => [],

    /**
     * Number of RoadRunner PHP worker processes to spawn.
     * Higher values increase throughput at the cost of memory.
     * Must be a positive integer.
     * Default: 4.
     */
    'workers' => (int) env('GRPC_WORKERS', 4),

    /**
     * Rate limiting settings for gRPC requests.
     * Used by the built-in RateLimitMiddleware.
     */
    'rate_limit' => [
        'max_attempts' => (int) env('GRPC_RATE_LIMIT', 60),
        'decay_minutes' => 1,
    ],

];
