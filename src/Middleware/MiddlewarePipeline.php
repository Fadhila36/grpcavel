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
        if (!is_array($globalMiddleware)) {
            $globalMiddleware = [];
        }
        
        // Final list: global -> class-level -> method-level
        $allMiddleware = array_merge($globalMiddleware, $middlewareClasses);

        $result = (new Pipeline(app()))
            ->send($request)
            ->through($allMiddleware)
            ->then(function ($req) use ($handler) {
                return $handler($req);
            });

        if (!is_object($result)) {
            throw new \RuntimeException('Middleware pipeline did not return an object');
        }

        return $result;
    }
}
