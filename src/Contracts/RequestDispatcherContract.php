<?php

declare(strict_types=1);

namespace Grpcavel\Contracts;

use Grpcavel\Discovery\ServiceDefinition;

/**
 * Contract for the central gRPC request dispatcher.
 */
interface RequestDispatcherContract
{
    /**
     * Dispatch an incoming gRPC request to its handler.
     *
     * @param  ServiceDefinition  $service  The discovered service metadata
     * @param  string             $method   The RPC method name being called
     * @param  string             $body     The binary protobuf request payload
     * @param  array<string, mixed> $context  The gRPC context/metadata
     * @return string|object  The binary protobuf response payload or DTO object
     */
    public function dispatch(
        ServiceDefinition $service,
        string $method,
        mixed $body,
        array $context
    ): mixed;
}
