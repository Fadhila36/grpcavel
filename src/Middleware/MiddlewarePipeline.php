<?php

declare(strict_types=1);

namespace Grpcavel\Middleware;

use Grpcavel\Contracts\MiddlewarePipelineContract;
use Illuminate\Pipeline\Pipeline;

final class MiddlewarePipeline implements MiddlewarePipelineContract
{
    /**
     * Run the given middleware classes around the handler.
     *
     * @param  array<string>  $middlewareClasses
     * @param  object         $request
     * @param  callable       $handler
     * @return object
     */
    public function run(array $middlewareClasses, object $request, callable $handler): object
    {
        $globalMiddleware = config('grpc.middleware', []);
        
        // Final list: global -> class-level -> method-level
        $allMiddleware = array_merge($globalMiddleware, $middlewareClasses);

        return (new Pipeline(app()))
            ->send($request)
            ->through($allMiddleware)
            ->then($handler);
    }
}
