<?php

declare(strict_types=1);

namespace Grpcavel\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

final class MakeClientCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grpc:make-client {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new gRPC client wrapper';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'gRPC Client';

    protected function getStub(): string
    {
        return __DIR__ . '/../../stubs/client.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Grpc\Clients';
    }
}
