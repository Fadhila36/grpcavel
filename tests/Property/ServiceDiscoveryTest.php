<?php

declare(strict_types=1);

use Grpcavel\Discovery\ServiceDiscoverer;

// ---------------------------------------------------------------------------
// Helpers for Property 5: Service Name Derivation
// ---------------------------------------------------------------------------

/**
 * Write a single #[GrpcService]-annotated class to a temp directory,
 * run the discoverer, clean up, and return the discovered service name.
 * Returns null if no service was discovered.
 */
function discoverServiceName(string $namespace, string $className): ?string
{
    // Ensure the fixture DTOs are available
    if (! class_exists('GrpcavelPropFixtures\\PropRequest')) {
        eval('namespace GrpcavelPropFixtures; class PropRequest {}');
    }
    if (! class_exists('GrpcavelPropFixtures\\PropResponse')) {
        eval('namespace GrpcavelPropFixtures; class PropResponse {}');
    }

    $dir = sys_get_temp_dir()
        . DIRECTORY_SEPARATOR
        . 'grpcavel_prop5_' . uniqid('', true);
    mkdir($dir, 0777, true);

    $source = <<<PHPSRC
    <?php
    namespace {$namespace};
    use Grpcavel\Attributes\GrpcService;
    use GrpcavelPropFixtures\PropRequest;
    use GrpcavelPropFixtures\PropResponse;
    #[GrpcService]
    class {$className} {
        public function handle(PropRequest \$req): PropResponse
        {
            return new PropResponse();
        }
    }
    PHPSRC;

    $file = $dir . DIRECTORY_SEPARATOR . $className . '.php';
    file_put_contents($file, $source);

    try {
        $discoverer = new ServiceDiscoverer($dir);
        $services = $discoverer->discover();

        return $services !== [] ? $services[0]->serviceName : null;
    } finally {
        if (file_exists($file)) {
            unlink($file);
        }
        rmdir($dir);
    }
}

/**
 * Generate a random alphabetic string of the given length.
 */
function randomAlpha(int $length): string
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $chars[random_int(0, strlen($chars) - 1)];
    }

    return $result;
}

/**
 * Generate a random PascalCase prefix (1-3 words, each 3-10 chars).
 * Guaranteed NOT to end in "Service".
 */
function randomPrefix(): string
{
    $wordCount = random_int(1, 3);
    $prefix = '';
    for ($i = 0; $i < $wordCount; $i++) {
        $len = random_int(3, 10);
        $word = strtoupper(randomAlpha(1)) . strtolower(randomAlpha($len - 1));
        $prefix .= $word;
    }

    // Ensure the prefix itself does not accidentally end in "Service"
    if (str_ends_with($prefix, 'Service')) {
        $prefix .= 'X';
    }

    return $prefix;
}

/**
 * Generate 5 [namespace, className, expectedDerivedName] tuples where
 * className = prefix . 'Service'.
 *
 * @return array<array{string, string, string}>
 */
function generateServiceSuffixCases(): array
{
    $cases = [];
    $seen = [];

    while (count($cases) < 5) {
        $prefix = randomPrefix();
        $className = $prefix . 'Service';

        // Avoid duplicate class names to prevent PHP redeclaration errors
        if (isset($seen[$className])) {
            continue;
        }
        $seen[$className] = true;

        // Replace dots in uniqid output to keep namespace a valid PHP identifier
        $namespace = 'GrpcavelPropNs' . str_replace('.', '_', uniqid('', true));
        $cases[] = [$namespace, $className, $prefix];
    }

    return $cases;
}

/**
 * Generate 5 [namespace, className, expectedDerivedName] tuples where
 * className does NOT end in 'Service', so the full class name is returned unchanged.
 *
 * @return array<array{string, string, string}>
 */
function generateNoSuffixCases(): array
{
    $cases = [];
    $seen = [];

    $suffixes = ['Handler', 'Controller', 'Manager', 'Worker', 'Processor', 'Resolver'];

    while (count($cases) < 5) {
        $prefix = randomPrefix();
        $suffix = $suffixes[array_rand($suffixes)];
        $className = $prefix . $suffix;

        // Ensure it really does not end in 'Service'
        if (str_ends_with($className, 'Service')) {
            continue;
        }

        if (isset($seen[$className])) {
            continue;
        }
        $seen[$className] = true;

        $namespace = 'GrpcavelPropNsNoSuffix' . str_replace('.', '_', uniqid('', true));
        $cases[] = [$namespace, $className, $className];
    }

    return $cases;
}

// ---------------------------------------------------------------------------
// Property 5: Service Name Derivation
// Validates: Requirements 2.6
// ---------------------------------------------------------------------------

/**
 * **Validates: Requirements 2.6**
 *
 * For any PHP class name ending in the suffix "Service" (e.g., UserService,
 * OrderService), when no explicit name parameter is provided to #[GrpcService],
 * the Framework SHALL derive the protobuf service name by stripping the
 * "Service" suffix.
 *
 * Generates 5 random class names ending in "Service" and asserts the
 * derived name equals the prefix (suffix stripped).
 */
it(
    'strips the "Service" suffix from any class name ending in "Service" when no explicit name is given',
    function (string $namespace, string $className, string $expectedName) {
        // Feature: grpcavel, Property 5: Service Name Derivation
        $derived = discoverServiceName($namespace, $className);

        expect($derived)->toBe($expectedName);
    }
)->with(generateServiceSuffixCases())
 ->group('Feature: grpcavel, Property 5: Service Name Derivation');

/**
 * **Validates: Requirements 2.6**
 *
 * For any PHP class name that does NOT end in "Service", when no explicit
 * name parameter is provided to #[GrpcService], the Framework SHALL return
 * the full class name unchanged.
 *
 * Generates 5 random class names not ending in "Service" and asserts the
 * derived name equals the full class name.
 */
it(
    'returns the full class name unchanged when the class name does not end in "Service"',
    function (string $namespace, string $className, string $expectedName) {
        // Feature: grpcavel, Property 5: Service Name Derivation
        $derived = discoverServiceName($namespace, $className);

        expect($derived)->toBe($expectedName);
    }
)->with(generateNoSuffixCases())
 ->group('Feature: grpcavel, Property 5: Service Name Derivation');

// ---------------------------------------------------------------------------
// Helpers shared across Property 6 and Property 7 tests
// ---------------------------------------------------------------------------

/**
 * Write a single PHP file to a temp directory, run ServiceDiscoverer on it,
 * and return the discovered handlers array (may be empty).
 *
 * @return array<\Grpcavel\Discovery\HandlerDefinition>
 */
function discoverHandlersFromSource(string $phpSource): array
{
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'grpcavel_prop6_' . uniqid('', true);
    mkdir($dir, 0777, true);
    $file = $dir . DIRECTORY_SEPARATOR . 'TestService.php';

    try {
        file_put_contents($file, $phpSource);
        $discoverer = new ServiceDiscoverer($dir);
        $services = $discoverer->discover();

        return $services !== [] ? $services[0]->handlers : [];
    } finally {
        if (file_exists($file)) {
            unlink($file);
        }
        if (is_dir($dir)) {
            rmdir($dir);
        }
    }
}

/**
 * Build a unique namespace + class name pair for each iteration to avoid
 * class-redeclaration collisions across the 100+ iterations.
 */
function uniqueServiceClass(int $iteration): array
{
    $ns = 'GrpcavelProp6Ns' . $iteration . '_' . mt_rand(1000, 9999);
    $cls = 'Svc' . $iteration . '_' . mt_rand(1000, 9999);

    return [$ns, $cls];
}

/**
 * Ensure a DTO stub class exists in the given namespace so the service source
 * can reference it by fully-qualified name.
 */
function ensureDtoClass(string $fqcn): void
{
    if (! class_exists($fqcn)) {
        // Split into namespace + short name
        $lastBackslash = strrpos($fqcn, '\\');
        if ($lastBackslash !== false) {
            $ns = substr($fqcn, 0, $lastBackslash);
            $short = substr($fqcn, $lastBackslash + 1);
            eval("namespace {$ns}; class {$short} {}");
        } else {
            eval("class {$fqcn} {}");
        }
    }
}

// ---------------------------------------------------------------------------
// Property 6: Handler Signature Validation — INVALID signatures
// ---------------------------------------------------------------------------

/**
 * **Validates: Requirements 2.3, 2.4**
 *
 * For any public method in a #[GrpcService]-annotated class, the
 * Service_Discoverer SHALL NOT register a method as a handler when it has
 * zero parameters.
 *
 * Generates 5 service classes each with a single zero-parameter method and
 * asserts that none are registered as handlers.
 */
it(
    'does not register methods with zero parameters as handlers',
    function (int $i): void {
        [$ns, $cls] = uniqueServiceClass($i);
        $reqNs = "GrpcavelProp6Req{$i}";
        $respNs = "GrpcavelProp6Resp{$i}";
        ensureDtoClass("{$reqNs}\\Req{$i}");
        ensureDtoClass("{$respNs}\\Resp{$i}");

        $source = <<<PHP
        <?php
        namespace {$ns};
        use Grpcavel\Attributes\GrpcService;
        #[GrpcService]
        class {$cls} {
            public function handle(): \\{$respNs}\\Resp{$i} {
                return new \\{$respNs}\\Resp{$i}();
            }
        }
        PHP;

        $handlers = discoverHandlersFromSource($source);

        expect($handlers)->toBeEmpty();
    }
)->with(range(1, 5))->group('Feature: grpcavel, Property 6: Handler Signature Validation');

/**
 * **Validates: Requirements 2.3, 2.4**
 *
 * For any public method in a #[GrpcService]-annotated class, the
 * Service_Discoverer SHALL NOT register a method as a handler when it has
 * two or more parameters.
 *
 * Generates 5 service classes each with a two-parameter method and asserts
 * that none are registered as handlers.
 */
it(
    'does not register methods with two or more parameters as handlers',
    function (int $i): void {
        [$ns, $cls] = uniqueServiceClass($i + 200);
        $reqNs = "GrpcavelProp6Req2p{$i}";
        $respNs = "GrpcavelProp6Resp2p{$i}";
        ensureDtoClass("{$reqNs}\\Req{$i}");
        ensureDtoClass("{$respNs}\\Resp{$i}");

        // Vary the number of extra parameters (2 to 4) across iterations
        $extraCount = ($i % 3) + 1; // 1, 2, or 3 extra params beyond the first
        $extraParams = implode(', ', array_map(
            fn (int $n) => "\\{$reqNs}\\Req{$i} \$extra{$n}",
            range(1, $extraCount)
        ));

        $source = <<<PHP
        <?php
        namespace {$ns};
        use Grpcavel\Attributes\GrpcService;
        #[GrpcService]
        class {$cls} {
            public function handle(\\{$reqNs}\\Req{$i} \$req, {$extraParams}): \\{$respNs}\\Resp{$i} {
                return new \\{$respNs}\\Resp{$i}();
            }
        }
        PHP;

        $handlers = discoverHandlersFromSource($source);

        expect($handlers)->toBeEmpty();
    }
)->with(range(1, 5))->group('Feature: grpcavel, Property 6: Handler Signature Validation');

/**
 * **Validates: Requirements 2.3, 2.4**
 *
 * For any public method in a #[GrpcService]-annotated class, the
 * Service_Discoverer SHALL NOT register a method as a handler when its single
 * parameter has a scalar (built-in) type.
 *
 * Generates 5 service classes cycling through scalar types (string, int,
 * float, bool, array, mixed) and asserts that none are registered as handlers.
 */
it(
    'does not register methods with a scalar parameter type as handlers',
    function (int $i): void {
        [$ns, $cls] = uniqueServiceClass($i + 400);
        $respNs = "GrpcavelProp6RespSc{$i}";
        ensureDtoClass("{$respNs}\\Resp{$i}");

        $scalarTypes = ['string', 'int', 'float', 'bool', 'array'];
        $scalarType = $scalarTypes[$i % count($scalarTypes)];

        $source = <<<PHP
        <?php
        namespace {$ns};
        use Grpcavel\Attributes\GrpcService;
        #[GrpcService]
        class {$cls} {
            public function handle({$scalarType} \$req): \\{$respNs}\\Resp{$i} {
                return new \\{$respNs}\\Resp{$i}();
            }
        }
        PHP;

        $handlers = discoverHandlersFromSource($source);

        expect($handlers)->toBeEmpty();
    }
)->with(range(1, 5))->group('Feature: grpcavel, Property 6: Handler Signature Validation');

/**
 * **Validates: Requirements 2.3, 2.4**
 *
 * For any public method in a #[GrpcService]-annotated class, the
 * Service_Discoverer SHALL NOT register a method as a handler when it has no
 * return type declaration.
 *
 * Generates 5 service classes each with a method that has no return type
 * and asserts that none are registered as handlers.
 */
it(
    'does not register methods with no return type declaration as handlers',
    function (int $i): void {
        [$ns, $cls] = uniqueServiceClass($i + 600);
        $reqNs = "GrpcavelProp6ReqNr{$i}";
        ensureDtoClass("{$reqNs}\\Req{$i}");

        $source = <<<PHP
        <?php
        namespace {$ns};
        use Grpcavel\Attributes\GrpcService;
        #[GrpcService]
        class {$cls} {
            public function handle(\\{$reqNs}\\Req{$i} \$req) {
            }
        }
        PHP;

        $handlers = discoverHandlersFromSource($source);

        expect($handlers)->toBeEmpty();
    }
)->with(range(1, 5))->group('Feature: grpcavel, Property 6: Handler Signature Validation');

/**
 * **Validates: Requirements 2.3, 2.4**
 *
 * For any public method in a #[GrpcService]-annotated class, the
 * Service_Discoverer SHALL NOT register a method as a handler when its return
 * type is a scalar (built-in) type.
 *
 * Generates 5 service classes cycling through scalar return types and
 * asserts that none are registered as handlers.
 */
it(
    'does not register methods with a scalar return type as handlers',
    function (int $i): void {
        [$ns, $cls] = uniqueServiceClass($i + 800);
        $reqNs = "GrpcavelProp6ReqSr{$i}";
        ensureDtoClass("{$reqNs}\\Req{$i}");

        $scalarReturnTypes = ['string', 'int', 'float', 'bool', 'array', 'void'];
        $returnType = $scalarReturnTypes[$i % count($scalarReturnTypes)];

        // void methods cannot have a return statement, others return a literal
        $returnStatement = $returnType === 'void' ? '' : "return ({$returnType}) '';";

        $source = <<<PHP
        <?php
        namespace {$ns};
        use Grpcavel\Attributes\GrpcService;
        #[GrpcService]
        class {$cls} {
            public function handle(\\{$reqNs}\\Req{$i} \$req): {$returnType} {
                {$returnStatement}
            }
        }
        PHP;

        $handlers = discoverHandlersFromSource($source);

        expect($handlers)->toBeEmpty();
    }
)->with(range(1, 5))->group('Feature: grpcavel, Property 6: Handler Signature Validation');

// ---------------------------------------------------------------------------
// Property 6: Handler Signature Validation — VALID signatures
// ---------------------------------------------------------------------------

/**
 * **Validates: Requirements 2.3, 2.4**
 *
 * For any public method in a #[GrpcService]-annotated class, the
 * Service_Discoverer SHALL register the method as a handler when it accepts
 * exactly one parameter whose type is a class (DTO) and declares a class
 * return type.
 *
 * Generates 5 service classes each with a single valid handler method and
 * asserts that every one is registered as a handler with the correct
 * requestClass and responseClass.
 */
it(
    'registers methods with exactly one class-typed parameter and a class return type as handlers',
    function (int $i): void {
        [$ns, $cls] = uniqueServiceClass($i + 1000);
        $reqNs = "GrpcavelProp6ReqV{$i}";
        $respNs = "GrpcavelProp6RespV{$i}";
        $reqClass = "Req{$i}";
        $respClass = "Resp{$i}";

        ensureDtoClass("{$reqNs}\\{$reqClass}");
        ensureDtoClass("{$respNs}\\{$respClass}");

        // Vary the method name across iterations to exercise different names
        $methodName = 'handle' . $i;

        $source = <<<PHP
        <?php
        namespace {$ns};
        use Grpcavel\Attributes\GrpcService;
        #[GrpcService]
        class {$cls} {
            public function {$methodName}(\\{$reqNs}\\{$reqClass} \$req): \\{$respNs}\\{$respClass} {
                return new \\{$respNs}\\{$respClass}();
            }
        }
        PHP;

        $handlers = discoverHandlersFromSource($source);

        expect($handlers)->toHaveCount(1);
        expect($handlers[0]->methodName)->toBe($methodName);
        expect($handlers[0]->requestClass)->toBe("{$reqNs}\\{$reqClass}");
        expect($handlers[0]->responseClass)->toBe("{$respNs}\\{$respClass}");
    }
)->with(range(1, 5))->group('Feature: grpcavel, Property 6: Handler Signature Validation');

// ---------------------------------------------------------------------------
// Property 7: Service Discovery Completeness
// Validates: Requirements 3.1, 3.2
// ---------------------------------------------------------------------------

/**
 * Generate a PHP source string for a minimal #[GrpcService]-annotated class.
 *
 * Each class lives in its own unique namespace to avoid symbol collisions
 * across iterations. The request/response stubs are inlined so the file is
 * self-contained and require_once can load it without side-effects.
 *
 * @param  string  $namespace  Unique namespace for this class
 * @param  string  $className  Unique class name (e.g. "PropSvc_abc123_0")
 * @param  string  $reqClass   Unique request stub class name
 * @param  string  $resClass   Unique response stub class name
 */
function buildGrpcServiceSource(
    string $namespace,
    string $className,
    string $reqClass,
    string $resClass,
): string {
    return <<<PHP
    <?php
    declare(strict_types=1);
    namespace {$namespace};
    use Grpcavel\Attributes\GrpcService;
    class {$reqClass} {}
    class {$resClass} {}
    #[GrpcService]
    class {$className} {
        public function handle({$reqClass} \$req): {$resClass}
        {
            return new {$resClass}();
        }
    }
    PHP;
}

/**
 * Write N service PHP files into a fresh temp directory, run ServiceDiscoverer,
 * clean up, and return the count of discovered services.
 *
 * @param  int     $n        Number of service files to generate
 * @param  string  $runId    Unique identifier for this test run (prevents class name collisions)
 * @return int               Number of services discovered
 */
function discoverNServices(int $n, string $runId): int
{
    $dir = sys_get_temp_dir()
        . DIRECTORY_SEPARATOR
        . 'grpcavel_prop7_' . $runId;

    mkdir($dir, 0777, true);

    $files = [];

    try {
        for ($i = 0; $i < $n; $i++) {
            $namespace = 'GrpcavelProp7\\' . $runId . '\\Svc' . $i;
            $className = 'PropService_' . $runId . '_' . $i;
            $reqClass  = 'PropReq_' . $runId . '_' . $i;
            $resClass  = 'PropRes_' . $runId . '_' . $i;

            $source   = buildGrpcServiceSource($namespace, $className, $reqClass, $resClass);
            $filePath = $dir . DIRECTORY_SEPARATOR . $className . '.php';

            file_put_contents($filePath, $source);
            $files[] = $filePath;
        }

        $discoverer = new ServiceDiscoverer($dir);

        return count($discoverer->discover());
    } finally {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        if (is_dir($dir)) {
            rmdir($dir);
        }
    }
}

/**
 * Dataset: at least 20 values of N covering 1–10 with some repetitions.
 *
 * Produces an array of [n, runId] pairs.
 * runId is unique per entry so class names never collide across iterations.
 *
 * Coverage: N=1..10 each appears at least twice → 20 entries minimum.
 */
$serviceCountDataset = (function (): array {
    $entries = [];

    // First pass: N = 1 through 10
    for ($n = 1; $n <= 10; $n++) {
        $runId    = 'r1n' . $n . 'x' . bin2hex(random_bytes(4));
        $entries[] = [$n, $runId];
    }

    // Second pass: N = 1 through 10 again (repetitions)
    for ($n = 1; $n <= 10; $n++) {
        $runId    = 'r2n' . $n . 'x' . bin2hex(random_bytes(4));
        $entries[] = [$n, $runId];
    }

    return $entries;
})();

/**
 * **Validates: Requirements 3.1, 3.2**
 *
 * Property 7: Service Discovery Completeness
 *
 * For any set of N PHP classes annotated with #[GrpcService] placed in the
 * configured services path, the ServiceDiscoverer SHALL register exactly N
 * services — no more, no fewer.
 *
 * Runs 20 iterations covering N=1..10 twice each (≥20 distinct inputs).
 */
it(
    'discovers exactly N services when N #[GrpcService] classes are placed in the services path',
    function (int $n, string $runId) {
        $discovered = discoverNServices($n, $runId);

        expect($discovered)->toBe($n);
    },
)->with($serviceCountDataset)
 ->group('Feature: grpcavel, Property 7: Service Discovery Completeness');
