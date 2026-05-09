<?php

declare(strict_types=1);

namespace Grpcavel\Tests\Feature;

test('globals')
    ->expect(['dd', 'dump', 'var_dump', 'print_r'])
    ->not->toBeUsed();

test('strict types')
    ->expect('Grpcavel')
    ->toUseStrictTypes();

test('contracts are interfaces')
    ->expect('Grpcavel\Contracts')
    ->toBeInterfaces();

test('attributes are classes')
    ->expect('Grpcavel\Attributes')
    ->toBeClasses();
