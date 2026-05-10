<?php

declare(strict_types=1);

namespace Grpcavel\Commands;

use Grpcavel\Contracts\ServiceDiscovererContract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class CacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grpc:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a metadata cache file for faster gRPC service discovery';

    public function handle(ServiceDiscovererContract $discoverer): int
    {
        $this->info('Discovering gRPC services for caching...');

        $services = $discoverer->discover();

        $path = config('grpc.cache_path');
        if (!is_string($path)) {
            $this->error('grpc.cache_path is not configured.');
            return self::FAILURE;
        }

        $directory = dirname($path);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $content = '<?php return ' . var_export($services, true) . ';' . PHP_EOL;

        File::put($path, $content);

        $this->info('gRPC service discovery cache created successfully!');

        return self::SUCCESS;
    }
}
