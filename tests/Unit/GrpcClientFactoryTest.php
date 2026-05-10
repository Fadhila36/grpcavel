<?php

declare(strict_types=1);

use Grpcavel\Http\GrpcClientFactory;

it('returns the same host if a string is passed', function () {
    $target = GrpcClientFactory::getTarget('127.0.0.1:9001');
    expect($target)->toBe('127.0.0.1:9001');
});

it('picks a host from an array randomly', function () {
    $hosts = ['10.0.0.1:9001', '10.0.0.2:9001', '10.0.0.3:9001'];
    
    $picked = [];
    for ($i = 0; $i < 100; $i++) {
        $picked[] = GrpcClientFactory::getTarget($hosts);
    }
    
    $uniquePicked = array_unique($picked);
    
    // We expect to have picked more than 1 unique host in 100 tries
    expect(count($uniquePicked))->toBeGreaterThan(1);
    
    // And all picked hosts must be in the original list
    foreach ($uniquePicked as $p) {
        expect($hosts)->toContain($p);
    }
});

it('throws exception if empty array is passed', function () {
    GrpcClientFactory::getTarget([]);
})->throws(InvalidArgumentException::class, 'Hosts list cannot be empty.');
