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

    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        /** @var string $methodName */
        $methodName = $properties['methodName'];
        /** @var string $rpcName */
        $rpcName = $properties['rpcName'];
        /** @var string $requestClass */
        $requestClass = $properties['requestClass'];
        /** @var string $responseClass */
        $responseClass = $properties['responseClass'];
        /** @var array<string> $middlewareClasses */
        $middlewareClasses = $properties['middlewareClasses'];

        return new self(
            methodName: $methodName,
            rpcName: $rpcName,
            requestClass: $requestClass,
            responseClass: $responseClass,
            middlewareClasses: $middlewareClasses,
        );
    }
}
