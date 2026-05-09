<?php

declare(strict_types=1);

namespace Grpcavel\Tests\Unit\Commands;

use Grpcavel\Tests\TestCase;
use Illuminate\Support\Facades\File;

class InstallCommandTest extends TestCase
{
    public function test_it_creates_directories_and_publishes_config(): void
    {
        // Use a temp root for testing file creation
        // Note: app_path() and config_path() might still point to real paths or Testbench paths.
        // We'll just verify the command runs and check the specific paths we can control.
        
        File::delete(base_path('.rr.yaml'));

        $this->artisan('grpc:install')
            ->expectsOutputToContain('Installing grpcavel')
            ->expectsOutputToContain('Publishing configuration')
            ->expectsOutputToContain('Creating directory structure')
            ->assertExitCode(0);

        // Verify some directories exist in the test environment
        $this->assertTrue(File::isDirectory(app_path('Grpc/Services')));
        $this->assertTrue(File::isDirectory(base_path('proto')));
    }
}
