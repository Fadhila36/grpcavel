<?php

declare(strict_types=1);

namespace Grpcavel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class MakeServiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grpc:make-service {name : The name of the service class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new gRPC service class';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        if (!is_string($name)) {
            $this->error('The name argument must be a string.');
            return self::FAILURE;
        }

        $className = Str::studly($name);
        
        if (! str_ends_with($className, 'Service')) {
            $className .= 'Service';
        }

        $path = app_path("Grpc/Services/{$className}.php");

        if (File::exists($path)) {
            $this->error("Service already exists: {$path}");
            return self::FAILURE;
        }

        $this->ensureDirectoryExists(dirname($path));

        $stub = $this->getStub($className);
        File::put($path, $stub);

        $this->info("Service created successfully: <fg=gray>app/Grpc/Services/{$className}.php</>");
        $this->line('Now you can define your handlers using the <fg=blue>#[GrpcMethod]</> attribute.');

        return self::SUCCESS;
    }

    /**
     * Ensure the directory exists.
     */
    private function ensureDirectoryExists(string $path): void
    {
        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    /**
     * Get the service stub.
     */
    private function getStub(string $className): string
    {
        $serviceName = str_replace('Service', '', $className);
        
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Grpc\Services;

use Grpcavel\Attributes\GrpcService;
use Grpcavel\Attributes\GrpcMethod;
// use App\Grpc\Requests\ExampleRequest;
// use App\Grpc\Responses\ExampleResponse;

#[GrpcService(name: '{$serviceName}')]
final class {$className}
{
    /**
     * Example gRPC handler.
     * 
     * To register this as a gRPC method:
     * 1. Create Request and Response DTOs using php artisan grpc:make-request/response
     * 2. Type-hint the request as the first parameter
     * 3. Type-hint the return type as the response class
     */
    /*
    #[GrpcMethod]
    public function sayHello(ExampleRequest \$request): ExampleResponse
    {
        return new ExampleResponse(
            message: 'Hello ' . \$request->name
        );
    }
    */
}
PHP;
    }
}
