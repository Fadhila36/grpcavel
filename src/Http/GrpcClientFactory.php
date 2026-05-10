<?php

declare(strict_types=1);

namespace Grpcavel\Http;

final class GrpcClientFactory
{
    /**
     * Get a target host from a list using simple random load balancing.
     *
     * @param array<string>|string $hosts List of hosts or a single host.
     * @return string Selected host.
     */
    public static function getTarget(array|string $hosts): string
    {
        if (is_string($hosts)) {
            return $hosts;
        }

        if (empty($hosts)) {
            throw new \InvalidArgumentException('Hosts list cannot be empty.');
        }

        // Simple Random Load Balancing
        return $hosts[array_rand($hosts)];
    }

    /**
     * Create a standard gRPC stub with load balancing.
     *
     * @template T of \Grpc\BaseStub
     * @param class-string<T> $stubClass
     * @param array<string>|string $hosts
     * @param array<string, mixed> $opts
     * @return T
     */
    public static function makeStub(string $stubClass, array|string $hosts, array $opts = []): object
    {
        $target = self::getTarget($hosts);

        if (!isset($opts['credentials']) && class_exists('\Grpc\ChannelCredentials')) {
            // Default to insecure if not specified and extension exists
            $opts['credentials'] = \Grpc\ChannelCredentials::createInsecure();
        }

        /** @var T */
        return new $stubClass($target, $opts);
    }
}
