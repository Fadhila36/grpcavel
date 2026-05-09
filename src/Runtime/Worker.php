<?php

declare(strict_types=1);

namespace Grpcavel\Runtime;

use Grpcavel\Contracts\RequestDispatcherContract;
use Grpcavel\Runtime\ServiceRegistry;
use Illuminate\Support\Facades\Log;
use Spiral\RoadRunner\GRPC\Server;
use Spiral\RoadRunner\Worker as RRWorker;

final class Worker
{
    public function __construct(
        private readonly Server $rrServer,
        private readonly ServiceRegistry $registry,
        private readonly RequestDispatcherContract $dispatcher,
    ) {}

    public function serve(): void
    {
        $this->registerShutdownHandler();

        foreach ($this->registry->all() as $definition) {
            $this->rrServer->registerService(
                $definition->serviceName,
                function (string $method, string $body, array $ctx) use ($definition) {
                    $this->ensureFreshState();

                    try {
                        return $this->dispatcher->dispatch($definition, $method, $body, $ctx);
                    } finally {
                        $this->cleanupState();
                    }
                }
            );
        }

        $this->rrServer->serve(RRWorker::create());
    }

    private function ensureFreshState(): void
    {
        // Ensure database connections are still alive
        if (app()->bound('db')) {
            foreach (app('db')->getConnections() as $connection) {
                try {
                    $connection->getPdo();
                } catch (\Exception) {
                    $connection->reconnect();
                }
            }
        }
    }

    private function cleanupState(): void
    {
        // Clear query logs to prevent memory leaks
        if (app()->bound('db')) {
            foreach (app('db')->getConnections() as $connection) {
                $connection->flushQueryLog();
            }
        }

        // Trigger garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    private function registerShutdownHandler(): void
    {
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
                Log::critical('gRPC worker fatal error', [
                    'message' => $error['message'],
                    'file'    => $error['file'],
                    'line'    => $error['line'],
                    'type'    => $error['type'],
                ]);
            }
        });
    }
}
