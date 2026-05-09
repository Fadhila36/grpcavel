<?php

declare(strict_types=1);

use Grpcavel\Attributes\GrpcMethod;
use Grpcavel\Attributes\GrpcService;
use Grpcavel\Attributes\Middleware;
use Grpcavel\Discovery\HandlerDefinition;
use Grpcavel\Discovery\ServiceDefinition;
use Grpcavel\Discovery\ServiceDiscoverer;
use Grpcavel\Runtime\ServiceRegistry;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create a temporary directory, write PHP files into it, run the discoverer,
 * then clean up. Returns the discovered ServiceDefinition array.
 *
 * @param  array<string, string>  $files  filename => PHP source
 * @return array<ServiceDefinition>
 */
function discoverFromFiles(array $files): array
{
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'grpcavel_test_' . uniqid('', true);
    mkdir($dir, 0777, true);

    try {
        foreach ($files as $filename => $source) {
            file_put_contents($dir . DIRECTORY_SEPARATOR . $filename, $source);
        }

        $discoverer = new ServiceDiscoverer($dir);

        return $discoverer->discover();
    } finally {
        // Clean up temp files
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.php') ?: [] as $f) {
            unlink($f);
        }
        rmdir($dir);
    }
}

// ---------------------------------------------------------------------------
// Fixture PHP source strings
// ---------------------------------------------------------------------------

// Minimal request/response stubs used by fixture services
$requestStub = <<<'PHP'
<?php
namespace GrpcavelTestFixtures;
class FooRequest {}
PHP;

$responseStub = <<<'PHP'
<?php
namespace GrpcavelTestFixtures;
class FooResponse {}
PHP;

$barRequestStub = <<<'PHP'
<?php
namespace GrpcavelTestFixtures;
class BarRequest {}
PHP;

$barResponseStub = <<<'PHP'
<?php
namespace GrpcavelTestFixtures;
class BarResponse {}
PHP;

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('ServiceDiscoverer', function () {

    // -----------------------------------------------------------------------
    // Path handling
    // -----------------------------------------------------------------------

    it('returns an empty array when the services path does not exist', function () {
        $discoverer = new ServiceDiscoverer('/non/existent/path/that/does/not/exist');

        expect($discoverer->discover())->toBeEmpty();
    });

    it('returns an empty array when the directory contains no PHP files', function () {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'grpcavel_empty_' . uniqid('', true);
        mkdir($dir, 0777, true);

        try {
            $discoverer = new ServiceDiscoverer($dir);
            expect($discoverer->discover())->toBeEmpty();
        } finally {
            rmdir($dir);
        }
    });

    // -----------------------------------------------------------------------
    // Non-annotated classes are ignored
    // -----------------------------------------------------------------------

    it('ignores PHP classes that do not carry #[GrpcService]', function () {
        $source = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\NoAttr;
        class PlainClass {
            public function handle(\GrpcavelTestFixtures\FooRequest $req): \GrpcavelTestFixtures\FooResponse
            {
                return new \GrpcavelTestFixtures\FooResponse();
            }
        }
        PHP;

        $services = discoverFromFiles(['PlainClass.php' => $source]);

        expect($services)->toBeEmpty();
    });

    // -----------------------------------------------------------------------
    // Basic discovery
    // -----------------------------------------------------------------------

    it('discovers a class annotated with #[GrpcService]', function () {
        // Ensure fixture DTOs are loaded
        if (! class_exists(\GrpcavelTestFixtures\FooRequest::class)) {
            eval('namespace GrpcavelTestFixtures; class FooRequest {}');
        }
        if (! class_exists(\GrpcavelTestFixtures\FooResponse::class)) {
            eval('namespace GrpcavelTestFixtures; class FooResponse {}');
        }

        $source = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\Basic;
        use Grpcavel\Attributes\GrpcService;
        use GrpcavelTestFixtures\FooRequest;
        use GrpcavelTestFixtures\FooResponse;
        #[GrpcService]
        class UserService {
            public function getUser(FooRequest $req): FooResponse
            {
                return new FooResponse();
            }
        }
        PHP;

        $services = discoverFromFiles(['UserService.php' => $source]);

        expect($services)->toHaveCount(1);
        expect($services[0])->toBeInstanceOf(ServiceDefinition::class);
        expect($services[0]->className)->toBe('GrpcavelTestFixtures\\Basic\\UserService');
    });

    // -----------------------------------------------------------------------
    // Service name derivation
    // -----------------------------------------------------------------------

    it('strips the "Service" suffix from the class name when no explicit name is given', function () {
        if (! class_exists(\GrpcavelTestFixtures\FooRequest::class)) {
            eval('namespace GrpcavelTestFixtures; class FooRequest {}');
        }
        if (! class_exists(\GrpcavelTestFixtures\FooResponse::class)) {
            eval('namespace GrpcavelTestFixtures; class FooResponse {}');
        }

        $source = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\NameDerive;
        use Grpcavel\Attributes\GrpcService;
        use GrpcavelTestFixtures\FooRequest;
        use GrpcavelTestFixtures\FooResponse;
        #[GrpcService]
        class OrderService {
            public function createOrder(FooRequest $req): FooResponse
            {
                return new FooResponse();
            }
        }
        PHP;

        $services = discoverFromFiles(['OrderService.php' => $source]);

        expect($services)->toHaveCount(1);
        expect($services[0]->serviceName)->toBe('Order');
    });

    it('uses the explicit name from #[GrpcService(name: ...)] when provided', function () {
        if (! class_exists(\GrpcavelTestFixtures\FooRequest::class)) {
            eval('namespace GrpcavelTestFixtures; class FooRequest {}');
        }
        if (! class_exists(\GrpcavelTestFixtures\FooResponse::class)) {
            eval('namespace GrpcavelTestFixtures; class FooResponse {}');
        }

        $source = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\ExplicitName;
        use Grpcavel\Attributes\GrpcService;
        use GrpcavelTestFixtures\FooRequest;
        use GrpcavelTestFixtures\FooResponse;
        #[GrpcService(name: 'MyCustomService')]
        class UserService {
            public function getUser(FooRequest $req): FooResponse
            {
                return new FooResponse();
            }
        }
        PHP;

        $services = discoverFromFiles(['UserService.php' => $source]);

        expect($services)->toHaveCount(1);
        expect($services[0]->serviceName)->toBe('MyCustomService');
    });

    it('keeps the full class name when it does not end in "Service"', function () {
        if (! class_exists(\GrpcavelTestFixtures\FooRequest::class)) {
            eval('namespace GrpcavelTestFixtures; class FooRequest {}');
        }
        if (! class_exists(\GrpcavelTestFixtures\FooResponse::class)) {
            eval('namespace GrpcavelTestFixtures; class FooResponse {}');
        }

        $source = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\NoSuffix;
        use Grpcavel\Attributes\GrpcService;
        use GrpcavelTestFixtures\FooRequest;
        use GrpcavelTestFixtures\FooResponse;
        #[GrpcService]
        class UserHandler {
            public function getUser(FooRequest $req): FooResponse
            {
                return new FooResponse();
            }
        }
        PHP;

        $services = discoverFromFiles(['UserHandler.php' => $source]);

        expect($services)->toHaveCount(1);
        expect($services[0]->serviceName)->toBe('UserHandler');
    });

    // -----------------------------------------------------------------------
    // Handler detection
    // -----------------------------------------------------------------------

    it('registers a public method with exactly one class-typed parameter and a class return type as a handler', function () {
        if (! class_exists(\GrpcavelTestFixtures\FooRequest::class)) {
            eval('namespace GrpcavelTestFixtures; class FooRequest {}');
        }
        if (! class_exists(\GrpcavelTestFixtures\FooResponse::class)) {
            eval('namespace GrpcavelTestFixtures; class FooResponse {}');
        }

        $source = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\HandlerDetect;
        use Grpcavel\Attributes\GrpcService;
        use GrpcavelTestFixtures\FooRequest;
        use GrpcavelTestFixtures\FooResponse;
        #[GrpcService]
        class ProductService {
            public function getProduct(FooRequest $req): FooResponse
            {
                return new FooResponse();
            }
        }
        PHP;

        $services = discoverFromFiles(['ProductService.php' => $source]);

        expect($services)->toHaveCount(1);
        expect($services[0]->handlers)->toHaveCount(1);

        $handler = $services[0]->handlers[0];
        expect($handler)->toBeInstanceOf(HandlerDefinition::class);
        expect($handler->methodName)->toBe('getProduct');
        expect($handler->requestClass)->toBe('GrpcavelTestFixtures\\FooRequest');
        expect($handler->responseClass)->toBe('GrpcavelTestFixtures\\FooResponse');
    });

    it('skips methods with zero parameters', function () {
        if (! class_exists(\GrpcavelTestFixtures\FooResponse::class)) {
            eval('namespace GrpcavelTestFixtures; class FooResponse {}');
        }

        $source = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\ZeroParams;
        use Grpcavel\Attributes\GrpcService;
        use GrpcavelTestFixtures\FooResponse;
        #[GrpcService]
        class CatalogService {
            public function listAll(): FooResponse
            {
                return new FooResponse();
            }
        }
        PHP;

        $services = discoverFromFiles(['CatalogService.php' => $source]);

        expect($services)->toHaveCount(1);
        expect($services[0]->handlers)->toBeEmpty();
    });

    it('skips methods with two or more parameters', function () {
        if (! class_exists(\GrpcavelTestFixtures\FooRequest::class)) {
            eval('namespace GrpcavelTestFixtures; class FooRequest {}');
        }
        if (! class_exists(\GrpcavelTestFixtures\FooResponse::class)) {
            eval('namespace GrpcavelTestFixtures; class FooResponse {}');
        }

        $source = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\TwoParams;
        use Grpcavel\Attributes\GrpcService;
        use GrpcavelTestFixtures\FooRequest;
        use GrpcavelTestFixtures\FooResponse;
        #[GrpcService]
        class SearchService {
            public function search(FooRequest $req, FooRequest $extra): FooResponse
            {
                return new FooResponse();
            }
        }
        PHP;

        $services = discoverFromFiles(['SearchService.php' => $source]);

        expect($services)->toHaveCount(1);
        expect($services[0]->handlers)->toBeEmpty();
    });

    it('skips methods with a scalar parameter type', function () {
        if (! class_exists(\GrpcavelTestFixtures\FooResponse::class)) {
            eval('namespace GrpcavelTestFixtures; class FooResponse {}');
        }

        $source = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\ScalarParam;
        use Grpcavel\Attributes\GrpcService;
        use GrpcavelTestFixtures\FooResponse;
        #[GrpcService]
        class NotificationService {
            public function notify(string $message): FooResponse
            {
                return new FooResponse();
            }
        }
        PHP;

        $services = discoverFromFiles(['NotificationService.php' => $source]);

        expect($services)->toHaveCount(1);
        expect($services[0]->handlers)->toBeEmpty();
    });

    it('skips methods with a scalar return type', function () {
        if (! class_exists(\GrpcavelTestFixtures\FooRequest::class)) {
            eval('namespace GrpcavelTestFixtures; class FooRequest {}');
        }

        $source = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\ScalarReturn;
        use Grpcavel\Attributes\GrpcService;
        use GrpcavelTestFixtures\FooRequest;
        #[GrpcService]
        class ReportService {
            public function generate(FooRequest $req): string
            {
                return 'ok';
            }
        }
        PHP;

        $services = discoverFromFiles(['ReportService.php' => $source]);

        expect($services)->toHaveCount(1);
        expect($services[0]->handlers)->toBeEmpty();
    });

    it('skips methods with no return type declaration', function () {
        if (! class_exists(\GrpcavelTestFixtures\FooRequest::class)) {
            eval('namespace GrpcavelTestFixtures; class FooRequest {}');
        }

        $source = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\NoReturn;
        use Grpcavel\Attributes\GrpcService;
        use GrpcavelTestFixtures\FooRequest;
        #[GrpcService]
        class LogService {
            public function log(FooRequest $req)
            {
            }
        }
        PHP;

        $services = discoverFromFiles(['LogService.php' => $source]);

        expect($services)->toHaveCount(1);
        expect($services[0]->handlers)->toBeEmpty();
    });

    it('skips magic methods (__construct, __toString, etc.)', function () {
        if (! class_exists(\GrpcavelTestFixtures\FooRequest::class)) {
            eval('namespace GrpcavelTestFixtures; class FooRequest {}');
        }
        if (! class_exists(\GrpcavelTestFixtures\FooResponse::class)) {
            eval('namespace GrpcavelTestFixtures; class FooResponse {}');
        }

        $source = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\MagicMethods;
        use Grpcavel\Attributes\GrpcService;
        use GrpcavelTestFixtures\FooRequest;
        use GrpcavelTestFixtures\FooResponse;
        #[GrpcService]
        class WidgetService {
            public function __construct() {}
            public function getWidget(FooRequest $req): FooResponse
            {
                return new FooResponse();
            }
        }
        PHP;

        $services = discoverFromFiles(['WidgetService.php' => $source]);

        expect($services)->toHaveCount(1);
        expect($services[0]->handlers)->toHaveCount(1);
        expect($services[0]->handlers[0]->methodName)->toBe('getWidget');
    });

    // -----------------------------------------------------------------------
    // GrpcMethod attribute — custom RPC name
    // -----------------------------------------------------------------------

    it('uses the rpc name from #[GrpcMethod(name: ...)] when provided', function () {
        if (! class_exists(\GrpcavelTestFixtures\FooRequest::class)) {
            eval('namespace GrpcavelTestFixtures; class FooRequest {}');
        }
        if (! class_exists(\GrpcavelTestFixtures\FooResponse::class)) {
            eval('namespace GrpcavelTestFixtures; class FooResponse {}');
        }

        $source = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\RpcName;
        use Grpcavel\Attributes\GrpcService;
        use Grpcavel\Attributes\GrpcMethod;
        use GrpcavelTestFixtures\FooRequest;
        use GrpcavelTestFixtures\FooResponse;
        #[GrpcService]
        class InvoiceService {
            #[GrpcMethod(name: 'FetchInvoice')]
            public function getInvoice(FooRequest $req): FooResponse
            {
                return new FooResponse();
            }
        }
        PHP;

        $services = discoverFromFiles(['InvoiceService.php' => $source]);

        expect($services)->toHaveCount(1);
        expect($services[0]->handlers)->toHaveCount(1);
        expect($services[0]->handlers[0]->rpcName)->toBe('FetchInvoice');
        expect($services[0]->handlers[0]->methodName)->toBe('getInvoice');
    });

    it('falls back to the method name as rpc name when #[GrpcMethod] has no name', function () {
        if (! class_exists(\GrpcavelTestFixtures\FooRequest::class)) {
            eval('namespace GrpcavelTestFixtures; class FooRequest {}');
        }
        if (! class_exists(\GrpcavelTestFixtures\FooResponse::class)) {
            eval('namespace GrpcavelTestFixtures; class FooResponse {}');
        }

        $source = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\RpcNameFallback;
        use Grpcavel\Attributes\GrpcService;
        use Grpcavel\Attributes\GrpcMethod;
        use GrpcavelTestFixtures\FooRequest;
        use GrpcavelTestFixtures\FooResponse;
        #[GrpcService]
        class ShipmentService {
            #[GrpcMethod]
            public function trackShipment(FooRequest $req): FooResponse
            {
                return new FooResponse();
            }
        }
        PHP;

        $services = discoverFromFiles(['ShipmentService.php' => $source]);

        expect($services)->toHaveCount(1);
        expect($services[0]->handlers[0]->rpcName)->toBe('trackShipment');
    });

    // -----------------------------------------------------------------------
    // Middleware extraction
    // -----------------------------------------------------------------------

    it('extracts class-level middleware from #[Middleware] attributes', function () {
        if (! class_exists(\GrpcavelTestFixtures\FooRequest::class)) {
            eval('namespace GrpcavelTestFixtures; class FooRequest {}');
        }
        if (! class_exists(\GrpcavelTestFixtures\FooResponse::class)) {
            eval('namespace GrpcavelTestFixtures; class FooResponse {}');
        }

        $source = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\ClassMiddleware;
        use Grpcavel\Attributes\GrpcService;
        use Grpcavel\Attributes\Middleware;
        use GrpcavelTestFixtures\FooRequest;
        use GrpcavelTestFixtures\FooResponse;
        #[GrpcService]
        #[Middleware('App\Grpc\Middleware\AuthMiddleware')]
        class AccountService {
            public function getAccount(FooRequest $req): FooResponse
            {
                return new FooResponse();
            }
        }
        PHP;

        $services = discoverFromFiles(['AccountService.php' => $source]);

        expect($services)->toHaveCount(1);
        expect($services[0]->middlewareClasses)->toBe(['App\Grpc\Middleware\AuthMiddleware']);
    });

    it('extracts method-level middleware from #[Middleware] attributes', function () {
        if (! class_exists(\GrpcavelTestFixtures\FooRequest::class)) {
            eval('namespace GrpcavelTestFixtures; class FooRequest {}');
        }
        if (! class_exists(\GrpcavelTestFixtures\FooResponse::class)) {
            eval('namespace GrpcavelTestFixtures; class FooResponse {}');
        }

        $source = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\MethodMiddleware;
        use Grpcavel\Attributes\GrpcService;
        use Grpcavel\Attributes\Middleware;
        use GrpcavelTestFixtures\FooRequest;
        use GrpcavelTestFixtures\FooResponse;
        #[GrpcService]
        class PaymentService {
            #[Middleware('App\Grpc\Middleware\LogMiddleware')]
            public function processPayment(FooRequest $req): FooResponse
            {
                return new FooResponse();
            }
        }
        PHP;

        $services = discoverFromFiles(['PaymentService.php' => $source]);

        expect($services)->toHaveCount(1);
        expect($services[0]->handlers)->toHaveCount(1);
        expect($services[0]->handlers[0]->middlewareClasses)->toBe(['App\Grpc\Middleware\LogMiddleware']);
    });

    it('extracts multiple repeatable #[Middleware] attributes on a class', function () {
        if (! class_exists(\GrpcavelTestFixtures\FooRequest::class)) {
            eval('namespace GrpcavelTestFixtures; class FooRequest {}');
        }
        if (! class_exists(\GrpcavelTestFixtures\FooResponse::class)) {
            eval('namespace GrpcavelTestFixtures; class FooResponse {}');
        }

        $source = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\MultiMiddleware;
        use Grpcavel\Attributes\GrpcService;
        use Grpcavel\Attributes\Middleware;
        use GrpcavelTestFixtures\FooRequest;
        use GrpcavelTestFixtures\FooResponse;
        #[GrpcService]
        #[Middleware('App\Grpc\Middleware\AuthMiddleware')]
        #[Middleware('App\Grpc\Middleware\LogMiddleware')]
        class SubscriptionService {
            public function subscribe(FooRequest $req): FooResponse
            {
                return new FooResponse();
            }
        }
        PHP;

        $services = discoverFromFiles(['SubscriptionService.php' => $source]);

        expect($services)->toHaveCount(1);
        expect($services[0]->middlewareClasses)->toBe([
            'App\Grpc\Middleware\AuthMiddleware',
            'App\Grpc\Middleware\LogMiddleware',
        ]);
    });

    // -----------------------------------------------------------------------
    // Package extraction
    // -----------------------------------------------------------------------

    it('stores the package from #[GrpcService(package: ...)]', function () {
        if (! class_exists(\GrpcavelTestFixtures\FooRequest::class)) {
            eval('namespace GrpcavelTestFixtures; class FooRequest {}');
        }
        if (! class_exists(\GrpcavelTestFixtures\FooResponse::class)) {
            eval('namespace GrpcavelTestFixtures; class FooResponse {}');
        }

        $source = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\PackageAttr;
        use Grpcavel\Attributes\GrpcService;
        use GrpcavelTestFixtures\FooRequest;
        use GrpcavelTestFixtures\FooResponse;
        #[GrpcService(package: 'com.example.billing')]
        class BillingService {
            public function charge(FooRequest $req): FooResponse
            {
                return new FooResponse();
            }
        }
        PHP;

        $services = discoverFromFiles(['BillingService.php' => $source]);

        expect($services)->toHaveCount(1);
        expect($services[0]->package)->toBe('com.example.billing');
    });

    // -----------------------------------------------------------------------
    // register() delegates to ServiceRegistry
    // -----------------------------------------------------------------------

    it('register() calls ServiceRegistry::register() for each discovered service', function () {
        if (! class_exists(\GrpcavelTestFixtures\FooRequest::class)) {
            eval('namespace GrpcavelTestFixtures; class FooRequest {}');
        }
        if (! class_exists(\GrpcavelTestFixtures\FooResponse::class)) {
            eval('namespace GrpcavelTestFixtures; class FooResponse {}');
        }

        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'grpcavel_reg_' . uniqid('', true);
        mkdir($dir, 0777, true);

        $source = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\Register;
        use Grpcavel\Attributes\GrpcService;
        use GrpcavelTestFixtures\FooRequest;
        use GrpcavelTestFixtures\FooResponse;
        #[GrpcService]
        class TeamService {
            public function getTeam(FooRequest $req): FooResponse
            {
                return new FooResponse();
            }
        }
        PHP;

        file_put_contents($dir . DIRECTORY_SEPARATOR . 'TeamService.php', $source);

        try {
            $discoverer = new ServiceDiscoverer($dir);
            $registry = new ServiceRegistry();

            $discoverer->register($registry);

            expect($registry->all())->toHaveCount(1);
            expect($registry->all()[0]->serviceName)->toBe('Team');
        } finally {
            unlink($dir . DIRECTORY_SEPARATOR . 'TeamService.php');
            rmdir($dir);
        }
    });

    // -----------------------------------------------------------------------
    // Multiple services in the same directory
    // -----------------------------------------------------------------------

    it('discovers multiple services from multiple files', function () {
        if (! class_exists(\GrpcavelTestFixtures\FooRequest::class)) {
            eval('namespace GrpcavelTestFixtures; class FooRequest {}');
        }
        if (! class_exists(\GrpcavelTestFixtures\FooResponse::class)) {
            eval('namespace GrpcavelTestFixtures; class FooResponse {}');
        }

        $source1 = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\Multi1;
        use Grpcavel\Attributes\GrpcService;
        use GrpcavelTestFixtures\FooRequest;
        use GrpcavelTestFixtures\FooResponse;
        #[GrpcService]
        class AlphaService {
            public function doAlpha(FooRequest $req): FooResponse { return new FooResponse(); }
        }
        PHP;

        $source2 = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\Multi2;
        use Grpcavel\Attributes\GrpcService;
        use GrpcavelTestFixtures\FooRequest;
        use GrpcavelTestFixtures\FooResponse;
        #[GrpcService]
        class BetaService {
            public function doBeta(FooRequest $req): FooResponse { return new FooResponse(); }
        }
        PHP;

        $services = discoverFromFiles([
            'AlphaService.php' => $source1,
            'BetaService.php' => $source2,
        ]);

        $names = array_map(fn ($s) => $s->serviceName, $services);
        sort($names);

        expect($services)->toHaveCount(2);
        expect($names)->toBe(['Alpha', 'Beta']);
    });

    // -----------------------------------------------------------------------
    // Multiple handlers on a single service
    // -----------------------------------------------------------------------

    it('discovers multiple handlers on a single service class', function () {
        if (! class_exists(\GrpcavelTestFixtures\FooRequest::class)) {
            eval('namespace GrpcavelTestFixtures; class FooRequest {}');
        }
        if (! class_exists(\GrpcavelTestFixtures\FooResponse::class)) {
            eval('namespace GrpcavelTestFixtures; class FooResponse {}');
        }

        $source = <<<'PHP'
        <?php
        namespace GrpcavelTestFixtures\MultiHandler;
        use Grpcavel\Attributes\GrpcService;
        use GrpcavelTestFixtures\FooRequest;
        use GrpcavelTestFixtures\FooResponse;
        #[GrpcService]
        class InventoryService {
            public function getItem(FooRequest $req): FooResponse { return new FooResponse(); }
            public function listItems(FooRequest $req): FooResponse { return new FooResponse(); }
            public function deleteItem(FooRequest $req): FooResponse { return new FooResponse(); }
        }
        PHP;

        $services = discoverFromFiles(['InventoryService.php' => $source]);

        expect($services)->toHaveCount(1);
        expect($services[0]->handlers)->toHaveCount(3);

        $methodNames = array_map(fn ($h) => $h->methodName, $services[0]->handlers);
        sort($methodNames);
        expect($methodNames)->toBe(['deleteItem', 'getItem', 'listItems']);
    });
});
