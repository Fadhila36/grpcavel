<?php

declare(strict_types=1);

namespace Grpcavel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class MakeRequestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grpc:make-request {name : The name of the request class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new gRPC request DTO';

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
        
        if (! str_ends_with($className, 'Request')) {
            $className .= 'Request';
        }

        $path = app_path("Grpc/Requests/{$className}.php");

        if (File::exists($path)) {
            $this->error("Request already exists: {$path}");
            return self::FAILURE;
        }

        $this->ensureDirectoryExists(dirname($path));

        $stub = $this->getStub($className);
        File::put($path, $stub);

        $this->info("Request created successfully: <fg=gray>app/Grpc/Requests/{$className}.php</>");

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
     * Get the request stub.
     */
    private function getStub(string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Grpc\Requests;

use Grpcavel\Http\GrpcRequest;

final class {$className} extends GrpcRequest
{
    public function __construct(
        // public readonly string \$name,
    ) {}

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // 'name' => 'required|string|max:255',
        ];
    }
}
PHP;
    }
}
