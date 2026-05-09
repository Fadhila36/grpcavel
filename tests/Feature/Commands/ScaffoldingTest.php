<?php

declare(strict_types=1);

namespace Grpcavel\Tests\Feature\Commands;

use Grpcavel\Tests\TestCase;
use Illuminate\Support\Facades\File;

class ScaffoldingTest extends TestCase
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
    }

    public function test_it_scaffolds_service(): void
    {
        $this->artisan('grpc:make-service', ['name' => 'User'])
            ->assertSuccessful()
            ->expectsOutputToContain('Service created successfully');

        $this->assertFileExists(app_path('Grpc/Services/UserService.php'));
    }

    public function test_it_scaffolds_request(): void
    {
        $this->artisan('grpc:make-request', ['name' => 'GetUser'])
            ->assertSuccessful()
            ->expectsOutputToContain('Request created successfully');

        $this->assertFileExists(app_path('Grpc/Requests/GetUserRequest.php'));
    }

    public function test_it_scaffolds_response(): void
    {
        $this->artisan('grpc:make-response', ['name' => 'User'])
            ->assertSuccessful()
            ->expectsOutputToContain('Response created successfully');

        $this->assertFileExists(app_path('Grpc/Responses/UserResponse.php'));
    }

    public function test_it_prevents_duplicate_service(): void
    {
        $this->artisan('grpc:make-service', ['name' => 'User']);
        
        $this->artisan('grpc:make-service', ['name' => 'User'])
            ->assertFailed()
            ->expectsOutputToContain('Service already exists');
    }
}
