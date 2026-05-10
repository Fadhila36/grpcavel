<?php

declare(strict_types=1);

namespace Grpcavel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grpc:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the grpcavel framework and prepare the environment';

    public function handle(): int
    {
        $this->info('Installing grpcavel...');

        $this->publishConfig();
        $this->createDirectories();
        $this->generateRoadRunnerConfig();

        $this->newLine();
        $this->info('grpcavel has been installed successfully!');
        $this->newLine();
        $this->info('Next steps:');
        $this->line(' 1. Define your first gRPC service: php artisan grpc:make-service');
        $this->line(' 2. Sync your proto files: php artisan grpc:sync');
        $this->line(' 3. Start the server: php artisan grpc:start');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Publish the configuration file.
     */
    private function publishConfig(): void
    {
        $this->comment('Publishing configuration...');

        $this->call('vendor:publish', [
            '--tag' => 'grpc-config',
        ]);
    }

    /**
     * Create the necessary directory structure.
     */
    private function createDirectories(): void
    {
        $this->comment('Creating directory structure...');

        $directories = [
            app_path('Grpc/Services'),
            app_path('Grpc/Requests'),
            app_path('Grpc/Responses'),
            app_path('Grpc/Middleware'),
            app_path('Grpc/Generated'),
            base_path('proto'),
        ];

        foreach ($directories as $directory) {
            if (! File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->line("  Created: <fg=gray>$directory</>");
            } else {
                $this->line("  Skipped: <fg=gray>$directory</> (already exists)");
            }
        }
    }

    /**
     * Generate the default .rr.yaml configuration file.
     */
    private function generateRoadRunnerConfig(): void
    {
        $path = base_path('.rr.yaml');

        if (File::exists($path)) {
            if (! $this->confirm('.rr.yaml already exists. Do you want to overwrite it?', false)) {
                $this->line('  Skipped .rr.yaml generation.');
                return;
            }
        }

        $this->comment('Generating .rr.yaml...');

        $config = $this->getRoadRunnerConfigTemplate();

        File::put($path, $config);

        $this->line('  Generated: <fg=gray>.rr.yaml</>');
    }

    /**
     * Get the default .rr.yaml template.
     */
    private function getRoadRunnerConfigTemplate(): string
    {
        $hostConfig = config('grpc.server.host', '0.0.0.0');
        $host = is_string($hostConfig) ? $hostConfig : '0.0.0.0';

        $portConfig = config('grpc.server.port', 9001);
        $port = is_int($portConfig) ? $portConfig : 9001;

        $workersConfig = config('grpc.workers', 4);
        $workers = is_int($workersConfig) ? $workersConfig : 4;

        return <<<YAML
version: "3"

grpc:
  listen: "tcp://$host:$port"
  proto:
    - "proto/*.proto"
  pool:
    num_workers: $workers
    max_jobs: 64
    supervisor:
      watch_tick: 1s
      ttl: 0s
      idle_ttl: 10s
      max_worker_memory: 128
      exec_ttl: 60s

server:
  command: "php artisan grpc:worker"
  relay: "pipes"

logs:
  mode: production
  level: info
YAML;
    }
}
