<?php

declare(strict_types=1);

namespace Grpcavel\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class GrpcService
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $package = null,
    ) {}
}
