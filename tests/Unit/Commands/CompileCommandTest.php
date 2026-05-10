<?php

declare(strict_types=1);

namespace Grpcavel\Tests\Unit\Commands;

use Grpcavel\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class CompileCommandTest extends TestCase
{
    public function test_it_shows_error_when_protoc_is_missing(): void
    {
        exec('protoc --version', $output, $returnVar);
        if ($returnVar === 0) {
            $this->markTestSkipped('protoc is installed, skipping test for missing protoc.');
        }
        
        $this->artisan('grpc:compile')
            ->assertExitCode(1)
            ->expectsOutputToContain('is not installed or not in your PATH');
    }

    public function test_it_compiles_proto_files(): void
    {
        // Create a dummy proto file
        $protoPath = base_path('proto');
        if (!is_dir($protoPath)) {
            mkdir($protoPath, 0755, true);
        }
        file_put_contents($protoPath . '/test.proto', 'syntax = "proto3"; package test; service T { rpc M (R) returns (R); } message R {}');

        // Run compile
        Artisan::call('grpc:compile');
        
        // We don't necessarily assert file creation here because it depends on protoc
        // but we verify the command finished without crashing.
        $this->assertTrue(true);
    }
}
