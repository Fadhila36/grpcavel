<?php

declare(strict_types=1);

namespace Grpcavel\Tests\Unit\Proto;

use Grpcavel\Discovery\HandlerDefinition;
use Grpcavel\Discovery\ServiceDefinition;
use Grpcavel\Proto\ProtoCompiler;
use Grpcavel\Proto\ProtoWriter;
use Grpcavel\Proto\TypeMapper;
use Illuminate\Support\Facades\File;
use Grpcavel\Tests\TestCase;

class ProtoCompilerTest extends TestCase
{
    private string $tempProtoPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempProtoPath = sys_get_temp_dir() . '/grpcavel_proto_test_' . uniqid();
        if (!is_dir($this->tempProtoPath)) {
            mkdir($this->tempProtoPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempProtoPath)) {
            File::deleteDirectory($this->tempProtoPath);
        }
        parent::tearDown();
    }

    public function test_it_compiles_services_to_proto_files(): void
    {
        // Mock dependencies
        $typeMapper = new TypeMapper();
        $protoWriter = new ProtoWriter();
        $compiler = new ProtoCompiler($typeMapper, $protoWriter, $this->tempProtoPath);

        // Define a dummy DTO class if it doesn't exist
        if (!class_exists('Tests\\Unit\\Proto\\DummyRequest')) {
            eval('namespace Tests\\Unit\\Proto; class DummyRequest { public string $name; }');
        }
        if (!class_exists('Tests\\Unit\\Proto\\DummyResponse')) {
            eval('namespace Tests\\Unit\\Proto; class DummyResponse { public string $message; }');
        }

        $service = new ServiceDefinition(
            className: 'Tests\\Unit\\Proto\\DummyService',
            serviceName: 'Dummy',
            package: 'test',
            handlers: [
                new HandlerDefinition(
                    methodName: 'sayHello',
                    rpcName: 'SayHello',
                    requestClass: 'Tests\\Unit\\Proto\\DummyRequest',
                    responseClass: 'Tests\\Unit\\Proto\\DummyResponse',
                    middlewareClasses: []
                )
            ],
            middlewareClasses: []
        );

        $paths = $compiler->compile([$service]);

        $this->assertCount(1, $paths);
        $this->assertFileExists($paths[0]);
        $this->assertStringContainsString('service Dummy', file_get_contents($paths[0]));
        $this->assertStringContainsString('rpc SayHello (DummyRequest) returns (DummyResponse)', file_get_contents($paths[0]));
        $this->assertStringContainsString('message DummyRequest', file_get_contents($paths[0]));
        $this->assertStringContainsString('message DummyResponse', file_get_contents($paths[0]));
    }
}
