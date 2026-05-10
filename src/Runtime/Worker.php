<?php

declare(strict_types=1);

namespace Grpcavel\Runtime;

use Grpcavel\Contracts\RequestDispatcherContract;
use Grpcavel\Runtime\ServiceRegistry;
use Illuminate\Support\Facades\Log;
use Spiral\RoadRunner\GRPC\Server;
use Spiral\RoadRunner\Worker as RRWorker;
use Spiral\RoadRunner\Payload;
use Spiral\RoadRunner\GRPC\Internal\CallContext;

final class Worker
{
    public function __construct(
        private readonly ServiceRegistry $registry,
        private readonly RequestDispatcherContract $dispatcher,
    ) {}

    public function serve(): void
    {
        $this->registerShutdownHandler();

        $worker = RRWorker::create();

        while (true) {
            $request = $worker->waitPayload();
            if ($request === null) {
                return;
            }

            try {
                $call = CallContext::decode($request->header);
                
                $definition = $this->registry->get($call->service);
                
                if (! $definition) {
                    $worker->respond(new Payload('', '{"error":"Service not found"}'));
                    continue;
                }

                $this->ensureFreshState();

                $response = $this->dispatcher->dispatch($definition, $call->method, $request->body, $call->context);

                if (!is_string($response)) {
                    throw new \RuntimeException('Response from dispatcher is not a string');
                }

                $worker->respond(new Payload($response, '{}'));
                
            } catch (\Throwable $e) {
                Log::error('gRPC worker error', ['exception' => $e]);
                $worker->error($e->getMessage());
            } finally {
                $this->cleanupState();
            }
        }
    }

    private function ensureFreshState(): void
    {
        // Ensure database connections are still alive
        if (app()->bound('db')) {
            /** @var \Illuminate\Database\DatabaseManager $db */
            $db = app('db');
            foreach ($db->getConnections() as $connection) {
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
            /** @var \Illuminate\Database\DatabaseManager $db */
            $db = app('db');
            foreach ($db->getConnections() as $connection) {
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
