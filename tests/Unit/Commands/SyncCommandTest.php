<?php

declare(strict_types=1);

namespace Grpcavel\Tests\Unit\Commands;

use Grpcavel\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SyncCommandTest extends TestCase
{
    private string $tempPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempPath = sys_get_temp_dir() . '/grpcavel_sync_test_' . uniqid();
        mkdir($this->tempPath, 0755, true);
        
        // Re-bind ServiceDiscoverer to use this temp path
        $this->app->config->set('grpc.services_path', $this->tempPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempPath);
        parent::tearDown();
    }

    public function test_it_reports_no_services_found(): void
    {
        $this->artisan('grpc:sync')
            ->expectsOutputToContain('No gRPC services discovered')
            ->assertExitCode(0);
    }

    public function test_it_syncs_discovered_services(): void
    {
        $className = 'SyncTestService' . uniqid();
        $reqName = 'SyncReq' . uniqid();
        $respName = 'SyncResp' . uniqid();

        // Create a dummy service class file
        $path = $this->tempPath . "/{$className}.php";
        $source = <<<PHP
<?php
namespace App\Grpc\Services;
use Grpcavel\Attributes\GrpcService;
#[GrpcService]
class {$className} {
    public function h(\App\Grpc\Requests\\{$reqName} \$r): \App\Grpc\Responses\\{$respName} {
        return new \App\Grpc\Responses\\{$respName}();
    }
}
PHP;
        file_put_contents($path, $source);

        // Eval the DTOs so they exist for reflection
        if (!class_exists("App\Grpc\Requests\\{$reqName}")) {
            eval("namespace App\Grpc\Requests; class {$reqName} { public string \$id; }");
        }
        if (!class_exists("App\Grpc\Responses\\{$respName}")) {
            eval("namespace App\Grpc\Responses; class {$respName} { public string \$id; }");
        }

        $this->artisan('grpc:sync')
            ->expectsOutputToContain('Found 1 service(s)')
            ->expectsOutputToContain('Sync complete')
            ->assertExitCode(0);
    }
}
