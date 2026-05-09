<?php

declare(strict_types=1);

namespace Grpcavel\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Repeated
{
    public function __construct(
        public readonly string $type,
    ) {}
}
