<?php

declare(strict_types=1);

namespace Grpcavel\Contracts;

use Grpcavel\Runtime\ServiceRegistry;

/**
 * Contract for discovering and registering gRPC services.
 */
interface ServiceDiscovererContract
{
    /**
     * Scan the configured services path and return all discovered service metadata.
     *
     * @return array<\Grpcavel\Discovery\ServiceDefinition>
     */
    public function discover(): array;

    /**
     * Register all discovered services into the ServiceRegistry.
     */
    public function register(ServiceRegistry $registry): void;
}
