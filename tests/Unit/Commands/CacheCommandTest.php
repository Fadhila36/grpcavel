<?php

use Grpcavel\Commands\CacheCommand;
use Grpcavel\Discovery\ServiceDiscoverer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

it('can cache service discovery', function () {
    $cachePath = config('grpc.cache_path');
    if (File::exists($cachePath)) {
        File::delete($cachePath);
    }

    Artisan::call('grpc:cache');

    expect(File::exists($cachePath))->toBeTrue();
    
    $cached = require $cachePath;
    expect($cached)->toBeArray();
});

it('can clear service discovery cache', function () {
    $cachePath = config('grpc.cache_path');
    File::ensureDirectoryExists(dirname($cachePath));
    File::put($cachePath, '<?php return [];');

    Artisan::call('grpc:clear');

    expect(File::exists($cachePath))->toBeFalse();
});
