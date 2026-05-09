<?php

declare(strict_types=1);

namespace Grpcavel\Contracts;

/**
 * Contract for compiling PHP service definitions into .proto files.
 */
interface ProtoCompilerContract
{
    /**
     * Generate .proto files for all given service definitions.
     *
     * @param  array<\Grpcavel\Discovery\ServiceDefinition>  $services
     * @return array<string>  Paths to generated .proto files
     */
    public function compile(array $services): array;
}
