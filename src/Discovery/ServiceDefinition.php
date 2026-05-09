<?php

declare(strict_types=1);

namespace Grpcavel\Discovery;

final readonly class ServiceDefinition
{
    public function __construct(
        public string $className,
        public string $serviceName,
        public string $package,
        /** @var array<HandlerDefinition> */
        public array $handlers,
        /** @var array<string> */
        public array $middlewareClasses,
    ) {}

    public static function __set_state(array $properties): self
    {
        return new self(
            className: $properties['className'],
            serviceName: $properties['serviceName'],
            package: $properties['package'],
            handlers: $properties['handlers'],
            middlewareClasses: $properties['middlewareClasses'],
        );
    }
}
