<?php

declare(strict_types=1);

use Grpcavel\Discovery\HandlerDefinition;
use Grpcavel\Discovery\ServiceDefinition;
use Grpcavel\Proto\ProtoCompiler;
use Grpcavel\Proto\ProtoWriter;
use Grpcavel\Proto\TypeMapper;
use Illuminate\Support\Facades\File;

// ---------------------------------------------------------------------------
// Helpers for Proto Property Tests
// ---------------------------------------------------------------------------

function compileToProto(ServiceDefinition $service): string
{
    $tempDir = sys_get_temp_dir() . '/grpcavel_prop_proto_' . uniqid();
    mkdir($tempDir, 0755, true);

    try {
        $typeMapper = new TypeMapper();
        $protoWriter = new ProtoWriter();
        $compiler = new ProtoCompiler($typeMapper, $protoWriter, $tempDir);

        $paths = $compiler->compile([$service]);
        return file_get_contents($paths[0]);
    } finally {
        File::deleteDirectory($tempDir);
    }
}

function randomName(int $length = 10): string
{
    return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'), 0, $length);
}

// ---------------------------------------------------------------------------
// Property 1: Syntax Header (Requirement 4.4)
// ---------------------------------------------------------------------------

it('always includes the proto3 syntax header as the first non-empty line', function () {
    $service = new ServiceDefinition(
        className: 'Svc',
        serviceName: 'Svc',
        package: '',
        handlers: [],
        middlewareClasses: []
    );

    $output = compileToProto($service);
    $lines = array_filter(explode("\n", $output), fn($l) => trim($l) !== '');
    
    expect(reset($lines))->toBe('syntax = "proto3";');
})->group('Feature: proto, Property 1: Syntax Header');

// ---------------------------------------------------------------------------
// Property 2: Package Declaration (Requirement 4.6)
// ---------------------------------------------------------------------------

it('includes the correct package declaration when provided', function (string $package) {
    $service = new ServiceDefinition(
        className: 'Svc',
        serviceName: 'Svc',
        package: $package,
        handlers: [],
        middlewareClasses: []
    );

    $output = compileToProto($service);
    if ($package === '') {
        expect($output)->not->toContain('package');
    } else {
        expect($output)->toContain("package {$package};");
    }
})->with(['', 'com.example', 'billing.v1'])->group('Feature: proto, Property 2: Package Declaration');

// ---------------------------------------------------------------------------
// Property 3: Service & RPC Integrity (Requirement 4.5, 4.7)
// ---------------------------------------------------------------------------

it('renders service and rpc names correctly', function (int $handlerCount) {
    $handlers = [];
    $expectedRpcs = [];
    
    // Ensure DTOs exist
    if (!class_exists('Prop\\Req')) eval('namespace Prop; class Req { public string $id; }');
    if (!class_exists('Prop\\Resp')) eval('namespace Prop; class Resp { public string $id; }');

    for ($i = 0; $i < $handlerCount; $i++) {
        $rpcName = 'Method' . $i;
        $handlers[] = new HandlerDefinition(
            methodName: 'handle' . $i,
            rpcName: $rpcName,
            requestClass: 'Prop\\Req',
            responseClass: 'Prop\\Resp',
            middlewareClasses: []
        );
        $expectedRpcs[] = "rpc {$rpcName} (Req) returns (Resp);";
    }

    $serviceName = 'TestService' . $handlerCount;
    $service = new ServiceDefinition(
        className: 'Svc',
        serviceName: $serviceName,
        package: 'test',
        handlers: $handlers,
        middlewareClasses: []
    );

    $output = compileToProto($service);
    
    expect($output)->toContain("service {$serviceName} {");
    foreach ($expectedRpcs as $rpc) {
        expect($output)->toContain($rpc);
    }
})->with(range(1, 5))->group('Feature: proto, Property 3: Service & RPC Integrity');

// ---------------------------------------------------------------------------
// Property 4: Message Consistency (Requirement 4.3, 4.8)
// ---------------------------------------------------------------------------

it('generates consistent message definitions for DTOs', function () {
    $className = 'PropDto' . uniqid();
    eval("namespace Prop; class {$className} { public string \$name; public int \$age; }");

    $service = new ServiceDefinition(
        className: 'Svc',
        serviceName: 'Svc',
        package: 'test',
        handlers: [
            new HandlerDefinition(
                methodName: 'h',
                rpcName: 'R',
                requestClass: "Prop\\{$className}",
                responseClass: "Prop\\{$className}",
                middlewareClasses: []
            )
        ],
        middlewareClasses: []
    );

    $output = compileToProto($service);
    
    expect($output)->toContain("message {$className} {");
    expect($output)->toContain("string name = 1;");
    expect($output)->toContain("int32 age = 2;");
})->group('Feature: proto, Property 4: Message Consistency');

// ---------------------------------------------------------------------------
// Property 8: Recursive Discovery (Requirement 4.10)
// ---------------------------------------------------------------------------

it('recursively discovers and generates nested DTO messages', function () {
    $childClass = 'ChildDto' . uniqid();
    $parentClass = 'ParentDto' . uniqid();
    
    eval("namespace Prop; class {$childClass} { public string \$data; }");
    eval("namespace Prop; class {$parentClass} { public \\Prop\\{$childClass} \$child; }");

    $service = new ServiceDefinition(
        className: 'Svc',
        serviceName: 'Svc',
        package: 'test',
        handlers: [
            new HandlerDefinition(
                methodName: 'h',
                rpcName: 'R',
                requestClass: "Prop\\{$parentClass}",
                responseClass: "Prop\\{$parentClass}",
                middlewareClasses: []
            )
        ],
        middlewareClasses: []
    );

    $output = compileToProto($service);
    
    expect($output)->toContain("message {$parentClass} {");
    expect($output)->toContain("{$childClass} child = 1;");
    expect($output)->toContain("message {$childClass} {");
    expect($output)->toContain("string data = 1;");
})->group('Feature: proto, Property 8: Recursive Discovery');
