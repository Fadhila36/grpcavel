<?php

declare(strict_types=1);

namespace Grpcavel\Testing;

use Grpcavel\Contracts\RequestDispatcherContract;
use Grpcavel\Runtime\ServiceRegistry;
use Illuminate\Support\Facades\App;

final class GrpcClient
{
    /**
     * Call a gRPC method in-process for testing.
     *
     * @param  string  $serviceName
     * @param  string  $method
     * @param  object  $request
     * @return object
     */
    public static function call(string $serviceName, string $method, object $request): object
    {
        /** @var ServiceRegistry $registry */
        $registry = App::make(ServiceRegistry::class);

        /** @var RequestDispatcherContract $dispatcher */
        $dispatcher = App::make(RequestDispatcherContract::class);

        $definition = null;
        foreach ($registry->all() as $def) {
            if ($def->serviceName === $serviceName || $def->className === $serviceName) {
                $definition = $def;
                break;
            }
        }

        if (! $definition) {
            throw new \RuntimeException("Service $serviceName not registered in registry.");
        }

        $body = $request;
        if (method_exists($request, 'serializeToString')) {
            $body = $request->serializeToString();
        }
        
        $responseBinary = $dispatcher->dispatch($definition, $method, $body, []);

        
        // Find handler to get response class
        $handler = null;
        foreach ($definition->handlers as $h) {
            if ($h->rpcName === $method) {
                $handler = $h;
                break;
            }
        }

        if (! $handler) {
            throw new \RuntimeException("Handler for method $method not found in service $serviceName.");
        }

        $response = $responseBinary;

        if (! is_object($response)) {
            $responseClass = $handler->responseClass;
            $response = new $responseClass();
            if (method_exists($response, 'mergeFromString')) {
                $response->mergeFromString($responseBinary);
            }
        }

        return $response;
    }
}
