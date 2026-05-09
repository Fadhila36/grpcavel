<?php

declare(strict_types=1);

namespace Grpcavel\Contracts;

/**
 * Contract for building and executing a middleware pipeline.
 */
interface MiddlewarePipelineContract
{
    /**
     * Build a pipeline from the given middleware stack and execute it.
     *
     * @param  array<string>  $middlewareClasses  Fully-qualified middleware class names
     * @param  object         $request            The incoming request DTO
     * @param  callable       $handler            The terminal handler callable
     * @return object         Response DTO
     */
    public function run(array $middlewareClasses, object $request, callable $handler): object;
}
