<?php

declare(strict_types=1);

namespace Grpcavel\Commands;

use Grpcavel\Contracts\ProtoCompilerContract;
use Grpcavel\Contracts\ServiceDiscovererContract;
use Illuminate\Console\Command;

final class SyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grpc:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync PHP service definitions with .proto files';

    public function handle(
        ServiceDiscovererContract $discoverer,
        ProtoCompilerContract $compiler
    ): int {
        $this->info('Discovering gRPC services...');

        $services = $discoverer->discover();

        if (empty($services)) {
            $this->warn('No gRPC services discovered in ' . config('grpc.services_path'));
            $this->line('Make sure your classes are annotated with <fg=blue>#[GrpcService]</>.');
            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d service(s). Generating .proto files...', count($services)));

        $paths = $compiler->compile($services);

        foreach ($paths as $path) {
            $this->line(sprintf('  Generated: <fg=gray>%s</>', basename($path)));
        }

        $this->newLine();
        $this->info('Compiling .proto files to PHP stubs...');

        try {
            $this->call('grpc:compile');
            $this->info('Sync complete!');
        } catch (\Exception $e) {
            $this->error('Failed to compile .proto files: ' . $e->getMessage());
            $this->line('You may need to run php artisan grpc:compile manually once protoc is installed.');
        }

        return self::SUCCESS;
    }
}
