<?php

declare(strict_types=1);

namespace Grpcavel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class MakeResponseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grpc:make-response {name : The name of the response class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new gRPC response DTO';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $className = Str::studly($name);
        
        if (! str_ends_with($className, 'Response')) {
            $className .= 'Response';
        }

        $path = app_path("Grpc/Responses/{$className}.php");

        if (File::exists($path)) {
            $this->error("Response already exists: {$path}");
            return self::FAILURE;
        }

        $this->ensureDirectoryExists(dirname($path));

        $stub = $this->getStub($className);
        File::put($path, $stub);

        $this->info("Response created successfully: <fg=gray>app/Grpc/Responses/{$className}.php</>");

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
     * Get the response stub.
     */
    private function getStub(string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Grpc\Responses;

use Grpcavel\Http\GrpcResponse;

final class {$className} extends GrpcResponse
{
    public function __construct(
        // public readonly string \$message,
    ) {}

    /*
    public static function fromUser(\App\Models\User \$user): self
    {
        return self::fromModel(\$user);
    }
    */
}
PHP;
    }
}
