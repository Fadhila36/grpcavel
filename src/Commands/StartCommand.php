<?php

declare(strict_types=1);

namespace Grpcavel\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

final class StartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grpc:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the RoadRunner gRPC server';

    public function handle(): int
    {
        $this->info('Starting RoadRunner gRPC server...');

        if (! file_exists(base_path('.rr.yaml'))) {
            $this->error('RoadRunner configuration file (.rr.yaml) not found.');
            $this->line('Run php artisan grpc:install to generate it.');
            return self::FAILURE;
        }

        $binary = $this->findRoadRunnerBinary();

        if (! $binary) {
            $this->error('RoadRunner binary (rr) not found.');
            $this->line('Please download it from https://roadrunner.dev/ or use <fg=blue>./vendor/bin/rr</> if installed via composer.');
            return self::FAILURE;
        }

        $process = new Process([$binary, 'serve'], base_path(), null, null, null);
        $process->setTty(Process::isTtySupported());
        
        return $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });
    }

    /**
     * Find the RoadRunner binary.
     */
    private function findRoadRunnerBinary(): ?string
    {
        $paths = [
            base_path('rr'),
            base_path('vendor/bin/rr'),
        ];

        foreach ($paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        // Check system path
        $process = new Process(['rr', '--version']);
        try {
            $process->run();
            if ($process->isSuccessful()) {
                return 'rr';
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return null;
    }
}
