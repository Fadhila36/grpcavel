<?php

declare(strict_types=1);

namespace Grpcavel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class ClearCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grpc:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove the gRPC service discovery cache file';

    public function handle(): int
    {
        $path = config('grpc.cache_path');

        if (!is_string($path)) {
            $this->info('No gRPC cache file found (cache path not configured).');
            return self::SUCCESS;
        }

        if (File::exists($path)) {
            File::delete($path);
            $this->info('gRPC service discovery cache cleared.');
        } else {
            $this->info('No gRPC cache file found.');
        }

        return self::SUCCESS;
    }
}
