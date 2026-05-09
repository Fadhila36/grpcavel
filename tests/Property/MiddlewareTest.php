<?php

declare(strict_types=1);

use Grpcavel\Middleware\MiddlewarePipeline;

// ---------------------------------------------------------------------------
// Property 12: Middleware Order Integrity
// ---------------------------------------------------------------------------

it('executes middleware in the exact order specified', function (array $mNames) {
    $pipeline = new MiddlewarePipeline();
    $request = new stdClass();
    $request->log = [];
    
    foreach ($mNames as $name) {
        app()->bind($name, function () use ($name) {
            return new class($name) {
                public function __construct(private string $name) {}
                public function handle($request, $next) {
                    $request->log[] = $this->name;
                    return $next($request);
                }
            };
        });
    }

    $handler = function ($req) {
        $req->log[] = 'handler';
        return (object) ['status' => 'ok'];
    };

    $pipeline->run($mNames, $request, $handler);
    
    $expected = array_merge($mNames, ['handler']);
    expect($request->log)->toBe($expected);
})->with([
    [['m1', 'm2', 'm3']],
    [['check1', 'check2', 'check3']],
    [['a', 'b']],
])->group('Feature: middleware, Property 12: Middleware Order');

// ---------------------------------------------------------------------------
// Property 13: Middleware Data Propagation
// ---------------------------------------------------------------------------

it('allows middleware to modify the request for subsequent layers', function () {
    $pipeline = new MiddlewarePipeline();
    $request = (object) ['count' => 0];
    
    app()->bind('inc', fn() => new class {
        public function handle($request, $next) {
            $request->count++;
            return $next($request);
        }
    });

    $handler = function ($req) {
        return (object) ['final_count' => $req->count];
    };

    $res = $pipeline->run(['inc', 'inc', 'inc'], $request, $handler);
    
    expect($res->final_count)->toBe(3);
})->group('Feature: middleware, Property 13: Data Propagation');
