<?php

declare(strict_types=1);

namespace Grpcavel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

final class CompileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grpc:compile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compile .proto files into PHP stubs using protoc';

    public function handle(): int
    {
        if (! $this->ensureProtocIsInstalled()) {
            return self::FAILURE;
        }

        $protoPath = (string) config('grpc.proto_path', base_path('proto'));
        $generatedPath = (string) config('grpc.generated_path', app_path('Grpc/Generated'));

        if (! File::isDirectory($protoPath)) {
            $this->error("Proto directory not found: $protoPath");
            return self::FAILURE;
        }

        if (! File::isDirectory($generatedPath)) {
            File::makeDirectory($generatedPath, 0755, true);
        }

        $protoFiles = File::files($protoPath);
        $protoFiles = array_filter($protoFiles, fn ($file) => $file->getExtension() === 'proto');

        if (empty($protoFiles)) {
            $this->warn('No .proto files found in ' . $protoPath);
            return self::SUCCESS;
        }

        $this->info(sprintf('Compiling %d .proto files...', count($protoFiles)));

        foreach ($protoFiles as $file) {
            $this->compileFile($file->getPathname(), $protoPath, $generatedPath);
        }

        $this->info('Compilation complete!');

        return self::SUCCESS;
    }

    /**
     * Check if protoc is installed and available.
     */
    private function ensureProtocIsInstalled(): bool
    {
        $process = new Process(['protoc', '--version']);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->newLine();
            $this->error('Error: "protoc" (Protocol Buffer Compiler) is not installed or not in your PATH.');
            $this->newLine();
            $this->line('To use grpcavel, you must install protoc and the gRPC PHP plugin.');
            $this->newLine();
            $this->line('Installation instructions:');
            $this->line('  - <fg=yellow>Windows</>: Download from https://github.com/protocolbuffers/protobuf/releases');
            $this->line('  - <fg=yellow>macOS</>: brew install protobuf');
            $this->line('  - <fg=yellow>Linux</>: sudo apt install -y protobuf-compiler');
            $this->newLine();
            
            return false;
        }

        return true;
    }

    /**
     * Compile a single .proto file.
     */
    private function compileFile(string $filePath, string $protoPath, string $generatedPath): void
    {
        $command = [
            'protoc',
            "--proto_path=$protoPath",
            "--php_out=$generatedPath",
            // We assume the grpc_php_plugin is in the PATH or installed as a plugin
            "--grpc_out=$generatedPath",
            $filePath
        ];

        $process = new Process($command);
        $process->run();

        if ($process->isSuccessful()) {
            $this->line(sprintf('  Compiled: <fg=gray>%s</>', basename($filePath)));
        } else {
            $this->line(sprintf('  Failed: <fg=gray>%s</>', basename($filePath)));
            $this->error($process->getErrorOutput());
        }
    }
}
