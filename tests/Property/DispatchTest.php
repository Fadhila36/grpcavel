<?php

declare(strict_types=1);

namespace Grpcavel\Tests\Property;

use Grpcavel\Contracts\ExceptionMapperContract;
use Grpcavel\Contracts\MiddlewarePipelineContract;
use Grpcavel\Contracts\SerializerContract;
use Grpcavel\Contracts\ValidatorContract;
use Grpcavel\Discovery\ServiceDefinition;
use Grpcavel\Runtime\RequestDispatcher;
use Grpcavel\Tests\TestCase;
use Spiral\RoadRunner\GRPC\Exception\GRPCException;

class DispatchTest extends TestCase
{
    public function test_it_returns_unimplemented_for_missing_methods(): void
    {
        $dispatcher = new RequestDispatcher(
            $this->app->make(MiddlewarePipelineContract::class),
            $this->app->make(ValidatorContract::class),
            $this->app->make(SerializerContract::class),
            $this->app->make(ExceptionMapperContract::class),
        );

        $service = new ServiceDefinition(
            className: 'TestService',
            serviceName: 'Test',
            package: 'test',
            handlers: [], // No handlers
            middlewareClasses: []
        );

        $this->expectException(GRPCException::class);
        $this->expectExceptionMessage('Method MissingMethod not found');
        
        try {
            $dispatcher->dispatch($service, 'MissingMethod', '', []);
        } catch (GRPCException $e) {
            $this->assertEquals(12, $e->getCode()); // UNIMPLEMENTED
            throw $e;
        }
    }
}
