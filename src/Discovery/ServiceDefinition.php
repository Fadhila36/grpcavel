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

    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        /** @var string $className */
        $className = $properties['className'];
        /** @var string $serviceName */
        $serviceName = $properties['serviceName'];
        /** @var string $package */
        $package = $properties['package'];
        /** @var array<HandlerDefinition> $handlers */
        $handlers = $properties['handlers'];
        /** @var array<string> $middlewareClasses */
        $middlewareClasses = $properties['middlewareClasses'];

        return new self(
            className: $className,
            serviceName: $serviceName,
            package: $package,
            handlers: $handlers,
            middlewareClasses: $middlewareClasses,
        );
    }
}
