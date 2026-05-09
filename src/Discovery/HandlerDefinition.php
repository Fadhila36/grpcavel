<?php

declare(strict_types=1);

namespace Grpcavel\Discovery;

final readonly class HandlerDefinition
{
    public function __construct(
        public string $methodName,
        public string $rpcName,
        public string $requestClass,
        public string $responseClass,
        /** @var array<string> */
        public array $middlewareClasses,
    ) {}

    public static function __set_state(array $properties): self
    {
        return new self(
            methodName: $properties['methodName'],
            rpcName: $properties['rpcName'],
            requestClass: $properties['requestClass'],
            responseClass: $properties['responseClass'],
            middlewareClasses: $properties['middlewareClasses'],
        );
    }
}
