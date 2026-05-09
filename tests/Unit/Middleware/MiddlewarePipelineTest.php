<?php

declare(strict_types=1);

namespace Grpcavel\Tests\Unit\Middleware;

use Grpcavel\Middleware\MiddlewarePipeline;
use Grpcavel\Tests\TestCase;
use stdClass;

class MiddlewarePipelineTest extends TestCase
{
    public function test_it_runs_middleware_in_order(): void
    {
        $pipeline = new MiddlewarePipeline();
        $request = new stdClass();
        $request->calls = [];

        $m1 = new class {
            public function handle($request, $next) {
                $request->calls[] = 'm1';
                return $next($request);
            }
        };
        $m2 = new class {
            public function handle($request, $next) {
                $request->calls[] = 'm2';
                return $next($request);
            }
        };

        // Bind them so the pipeline can resolve them (since it uses app())
        $this->app->bind('m1', fn() => $m1);
        $this->app->bind('m2', fn() => $m2);

        $handler = function ($req) {
            $req->calls[] = 'handler';
            return (object) ['status' => 'ok'];
        };

        $res = $pipeline->run(['m1', 'm2'], $request, $handler);

        $this->assertEquals(['m1', 'm2', 'handler'], $request->calls);
        $this->assertEquals('ok', $res->status);
    }

    public function test_middleware_can_terminate_early(): void
    {
        $pipeline = new MiddlewarePipeline();
        $request = new stdClass();
        $request->calls = [];

        $m1 = new class {
            public function handle($request, $next) {
                $request->calls[] = 'm1';
                return (object) ['status' => 'early_exit'];
            }
        };

        $this->app->bind('m1', fn() => $m1);

        $handler = function ($req) {
            $req->calls[] = 'handler';
            return (object) ['status' => 'ok'];
        };

        $res = $pipeline->run(['m1'], $request, $handler);

        $this->assertEquals(['m1'], $request->calls);
        $this->assertEquals('early_exit', $res->status);
    }
}
