<?php

declare(strict_types=1);

namespace Grpcavel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class KubernetesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grpc:k8s {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Dockerfile and Kubernetes manifests for the gRPC service';

    public function handle(): int
    {
        $this->info('Generating Kubernetes manifests and Dockerfile...');

        $appName = $this->ask('What is the name of your application?', config('app.name', 'laravel'));
        $appName = Str::slug($appName);
        
        $imageName = $this->ask('What is the Docker image name?', "$appName:latest");

        $this->generateDockerfile();
        $this->generateK8sManifests($appName, $imageName);

        $this->newLine();
        $this->info('Kubernetes files generated successfully!');
        $this->newLine();
        $this->line('Next steps:');
        $this->line(' 1. Build your docker image: <fg=blue>docker build -t ' . $imageName . ' .</>');
        $this->line(' 2. Apply the manifests: <fg=blue>kubectl apply -f kubernetes/</>');
        $this->newLine();

        return self::SUCCESS;
    }

    private function generateDockerfile(): void
    {
        $path = base_path('Dockerfile');

        if (File::exists($path) && ! $this->option('force')) {
            if (! $this->confirm('Dockerfile already exists. Do you want to overwrite it?', false)) {
                $this->line('  Skipped Dockerfile generation.');
                return;
            }
        }

        $stubPath = __DIR__ . '/../../stubs/dockerfile.stub';
        
        if (! File::exists($stubPath)) {
            $this->error('Dockerfile stub not found.');
            return;
        }

        $content = File::get($stubPath);
        File::put($path, $content);
        
        $this->line('  Generated: <fg=gray>Dockerfile</>');
    }

    private function generateK8sManifests(string $appName, string $imageName): void
    {
        $dir = base_path('kubernetes');
        
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $this->generateFileFromStub($dir . '/deployment.yaml', 'deployment.stub', [
            '{{APP_NAME}}' => $appName,
            '{{DOCKER_IMAGE}}' => $imageName,
        ]);

        $this->generateFileFromStub($dir . '/service.yaml', 'service.stub', [
            '{{APP_NAME}}' => $appName,
        ]);
    }

    private function generateFileFromStub(string $targetPath, string $stubName, array $replacements): void
    {
        if (File::exists($targetPath) && ! $this->option('force')) {
            if (! $this->confirm(basename($targetPath) . ' already exists. Do you want to overwrite it?', false)) {
                $this->line('  Skipped ' . basename($targetPath) . ' generation.');
                return;
            }
        }

        $stubPath = __DIR__ . '/../../stubs/' . $stubName;

        if (! File::exists($stubPath)) {
            $this->error("Stub not found: $stubName");
            return;
        }

        $content = File::get($stubPath);

        foreach ($replacements as $key => $value) {
            $content = str_replace($key, $value, $content);
        }

        File::put($targetPath, $content);
        $this->line('  Generated: <fg=gray>kubernetes/' . basename($targetPath) . '</>');
    }
}
