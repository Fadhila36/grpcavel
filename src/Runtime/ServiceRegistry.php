<?php

declare(strict_types=1);

namespace Grpcavel\Runtime;

use Grpcavel\Discovery\HandlerDefinition;
use Grpcavel\Discovery\ServiceDefinition;

final class ServiceRegistry
{
    /** @var array<string, ServiceDefinition> */
    private array $services = [];

    /**
     * Register a service definition, keyed by its service name.
     */
    public function register(ServiceDefinition $definition): void
    {
        $this->services[$definition->serviceName] = $definition;
    }

    /**
     * Find the handler definition for a given service name and RPC method name.
     * Returns null if the service or method is not registered.
     */
    public function find(string $serviceName, string $method): ?HandlerDefinition
    {
        if (! isset($this->services[$serviceName])) {
            return null;
        }

        foreach ($this->services[$serviceName]->handlers as $handler) {
            if ($handler->rpcName === $method) {
                return $handler;
            }
        }

        return null;
    }

    /**
     * Return all registered service definitions.
     *
     * @return array<ServiceDefinition>
     */
    public function all(): array
    {
        return array_values($this->services);
    }
}
