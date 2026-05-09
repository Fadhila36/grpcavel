<?php

declare(strict_types=1);

namespace Grpcavel\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class GrpcMethod
{
    public function __construct(
        public readonly ?string $name = null,
    ) {}
}
