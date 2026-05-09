<?php

declare(strict_types=1);

namespace Grpcavel\Tests\Feature\Commands;

use Grpcavel\Tests\TestCase;
use Illuminate\Support\Facades\File;

class LifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanUp();
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
        parent::tearDown();
    }

    private function cleanUp(): void
    {
        File::deleteDirectory(app_path('Grpc'));
        File::deleteDirectory(base_path('proto'));
        File::delete(base_path('.rr.yaml'));
        File::delete(config_path('grpc.php'));
    }

    public function test_it_installs_framework(): void
    {
        // The command only asks if .rr.yaml already exists. 
        // In a clean environment, it just creates it.
        $this->artisan('grpc:install')
            ->assertSuccessful();

        $this->assertDirectoryExists(app_path('Grpc/Services'));
        $this->assertDirectoryExists(base_path('proto'));
        $this->assertFileExists(base_path('.rr.yaml'));
        $this->assertFileExists(config_path('grpc.php'));
    }

    public function test_it_syncs_services(): void
    {
        $this->artisan('grpc:install')
            ->assertSuccessful();

        $this->artisan('grpc:make-service', ['name' => 'User']);
        
        $this->artisan('grpc:sync')
            ->assertSuccessful()
            ->expectsOutputToContain('Sync complete');

        $this->assertFileExists(base_path('proto/User.proto'));
    }
}
