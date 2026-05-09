<?php

declare(strict_types=1);

use Grpcavel\Discovery\HandlerDefinition;
use Grpcavel\Discovery\ServiceDefinition;
use Grpcavel\Runtime\ServiceRegistry;

// Helper to build a HandlerDefinition
function makeHandler(string $rpcName, string $methodName = 'handle'): HandlerDefinition
{
    return new HandlerDefinition(
        methodName: $methodName,
        rpcName: $rpcName,
        requestClass: 'App\\Grpc\\Requests\\FooRequest',
        responseClass: 'App\\Grpc\\Responses\\FooResponse',
        middlewareClasses: [],
    );
}

// Helper to build a ServiceDefinition
function makeService(string $serviceName, array $handlers = [], string $className = 'App\\Grpc\\Services\\FooService'): ServiceDefinition
{
    return new ServiceDefinition(
        className: $className,
        serviceName: $serviceName,
        package: 'app',
        handlers: $handlers,
        middlewareClasses: [],
    );
}

describe('ServiceRegistry', function () {
    it('starts empty', function () {
        $registry = new ServiceRegistry();

        expect($registry->all())->toBeEmpty();
    });

    it('registers a service definition', function () {
        $registry = new ServiceRegistry();
        $service = makeService('UserService');

        $registry->register($service);

        expect($registry->all())->toHaveCount(1);
        expect($registry->all()[0])->toBe($service);
    });

    it('registers multiple distinct services', function () {
        $registry = new ServiceRegistry();
        $service1 = makeService('UserService', [], 'App\\UserService');
        $service2 = makeService('OrderService', [], 'App\\OrderService');

        $registry->register($service1);
        $registry->register($service2);

        expect($registry->all())->toHaveCount(2);
    });

    it('overwrites a service when registered with the same service name', function () {
        $registry = new ServiceRegistry();
        $original = makeService('UserService', [], 'App\\UserServiceV1');
        $replacement = makeService('UserService', [], 'App\\UserServiceV2');

        $registry->register($original);
        $registry->register($replacement);

        expect($registry->all())->toHaveCount(1);
        expect($registry->all()[0]->className)->toBe('App\\UserServiceV2');
    });

    it('returns null when finding a service that is not registered', function () {
        $registry = new ServiceRegistry();

        expect($registry->find('NonExistent', 'GetUser'))->toBeNull();
    });

    it('returns null when the service exists but the method is not registered', function () {
        $registry = new ServiceRegistry();
        $handler = makeHandler('GetUser');
        $service = makeService('UserService', [$handler]);

        $registry->register($service);

        expect($registry->find('UserService', 'DeleteUser'))->toBeNull();
    });

    it('finds a handler by service name and rpc method name', function () {
        $registry = new ServiceRegistry();
        $handler = makeHandler('GetUser');
        $service = makeService('UserService', [$handler]);

        $registry->register($service);

        $found = $registry->find('UserService', 'GetUser');

        expect($found)->toBe($handler);
    });

    it('finds the correct handler when a service has multiple handlers', function () {
        $registry = new ServiceRegistry();
        $getUser = makeHandler('GetUser', 'getUser');
        $createUser = makeHandler('CreateUser', 'createUser');
        $deleteUser = makeHandler('DeleteUser', 'deleteUser');
        $service = makeService('UserService', [$getUser, $createUser, $deleteUser]);

        $registry->register($service);

        expect($registry->find('UserService', 'GetUser'))->toBe($getUser);
        expect($registry->find('UserService', 'CreateUser'))->toBe($createUser);
        expect($registry->find('UserService', 'DeleteUser'))->toBe($deleteUser);
    });

    it('returns null when finding a method on a different service', function () {
        $registry = new ServiceRegistry();
        $handler = makeHandler('GetUser');
        $service = makeService('UserService', [$handler]);

        $registry->register($service);

        expect($registry->find('OrderService', 'GetUser'))->toBeNull();
    });

    it('all() returns a list (not a keyed map)', function () {
        $registry = new ServiceRegistry();
        $service1 = makeService('UserService', [], 'App\\UserService');
        $service2 = makeService('OrderService', [], 'App\\OrderService');

        $registry->register($service1);
        $registry->register($service2);

        $all = $registry->all();

        // Keys must be sequential integers (array_values result)
        expect(array_keys($all))->toBe([0, 1]);
    });

    it('find returns null for empty string service name', function () {
        $registry = new ServiceRegistry();

        expect($registry->find('', 'GetUser'))->toBeNull();
    });

    it('find returns null for empty string method name', function () {
        $registry = new ServiceRegistry();
        $handler = makeHandler('GetUser');
        $service = makeService('UserService', [$handler]);

        $registry->register($service);

        expect($registry->find('UserService', ''))->toBeNull();
    });

    it('find is case-sensitive for service name', function () {
        $registry = new ServiceRegistry();
        $handler = makeHandler('GetUser');
        $service = makeService('UserService', [$handler]);

        $registry->register($service);

        expect($registry->find('userservice', 'GetUser'))->toBeNull();
        expect($registry->find('USERSERVICE', 'GetUser'))->toBeNull();
        expect($registry->find('UserService', 'GetUser'))->not->toBeNull();
    });

    it('find is case-sensitive for method name', function () {
        $registry = new ServiceRegistry();
        $handler = makeHandler('GetUser');
        $service = makeService('UserService', [$handler]);

        $registry->register($service);

        expect($registry->find('UserService', 'getuser'))->toBeNull();
        expect($registry->find('UserService', 'GETUSER'))->toBeNull();
        expect($registry->find('UserService', 'GetUser'))->not->toBeNull();
    });
});
