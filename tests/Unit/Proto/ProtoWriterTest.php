<?php

declare(strict_types=1);

use Grpcavel\Discovery\HandlerDefinition;
use Grpcavel\Discovery\ServiceDefinition;
use Grpcavel\Proto\ProtoWriter;

// ---------------------------------------------------------------------------
// Helpers (prefixed with `pw_` to avoid collisions with other test files)
// ---------------------------------------------------------------------------

/**
 * Build a minimal ServiceDefinition for use in ProtoWriter tests.
 *
 * @param  array<HandlerDefinition>  $handlers
 */
function pw_makeService(
    string $serviceName,
    string $package = '',
    array $handlers = [],
): ServiceDefinition {
    return new ServiceDefinition(
        className: 'App\\Grpc\\Services\\' . $serviceName . 'Service',
        serviceName: $serviceName,
        package: $package,
        handlers: $handlers,
        middlewareClasses: [],
    );
}

/**
 * Build a HandlerDefinition for use in ProtoWriter tests.
 */
function pw_makeHandler(
    string $rpcName,
    string $requestClass,
    string $responseClass,
): HandlerDefinition {
    return new HandlerDefinition(
        methodName: lcfirst($rpcName),
        rpcName: $rpcName,
        requestClass: $requestClass,
        responseClass: $responseClass,
        middlewareClasses: [],
    );
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('ProtoWriter', function () {
    beforeEach(function () {
        $this->writer = new ProtoWriter();
    });

    // ── Header ───────────────────────────────────────────────────────────────

    it('always emits proto3 syntax declaration', function () {
        $service = pw_makeService('User');
        $output = $this->writer->write($service, []);

        expect($output)->toContain('syntax = "proto3";');
    });

    it('emits the syntax declaration as the very first line', function () {
        $service = pw_makeService('User');
        $output = $this->writer->write($service, []);

        expect(explode("\n", $output)[0])->toBe('syntax = "proto3";');
    });

    // ── Package ───────────────────────────────────────────────────────────────

    it('emits a package line when package is non-empty', function () {
        $service = pw_makeService('User', 'myapp');
        $output = $this->writer->write($service, []);

        expect($output)->toContain('package myapp;');
    });

    it('omits the package line when package is an empty string', function () {
        $service = pw_makeService('User', '');
        $output = $this->writer->write($service, []);

        expect($output)->not->toContain('package');
    });

    // ── Service block ─────────────────────────────────────────────────────────

    it('emits a service block with the correct service name', function () {
        $service = pw_makeService('User');
        $output = $this->writer->write($service, []);

        expect($output)->toContain('service User {');
    });

    it('emits an rpc entry for each handler', function () {
        $service = pw_makeService('User', '', [
            pw_makeHandler('GetUser', 'GetUserRequest', 'GetUserResponse'),
            pw_makeHandler('ListUsers', 'ListUsersRequest', 'ListUsersResponse'),
        ]);

        $output = $this->writer->write($service, []);

        expect($output)
            ->toContain('rpc GetUser (GetUserRequest) returns (GetUserResponse);')
            ->toContain('rpc ListUsers (ListUsersRequest) returns (ListUsersResponse);');
    });

    it('uses the short class name for fully-qualified request/response classes', function () {
        $service = pw_makeService('User', '', [
            pw_makeHandler(
                'GetUser',
                'App\\Grpc\\Requests\\GetUserRequest',
                'App\\Grpc\\Responses\\GetUserResponse',
            ),
        ]);

        $output = $this->writer->write($service, []);

        expect($output)->toContain('rpc GetUser (GetUserRequest) returns (GetUserResponse);');
    });

    it('emits an empty service block when there are no handlers', function () {
        $service = pw_makeService('Empty');
        $output = $this->writer->write($service, []);

        expect($output)->toContain("service Empty {\n}");
    });

    // ── Message blocks ────────────────────────────────────────────────────────

    it('emits a message block for each message definition', function () {
        $service = pw_makeService('User');
        $messages = [
            [
                'name'   => 'GetUserRequest',
                'fields' => [
                    ['name' => 'id', 'protoType' => 'int32', 'fieldNumber' => 1],
                ],
            ],
            [
                'name'   => 'GetUserResponse',
                'fields' => [
                    ['name' => 'name', 'protoType' => 'string', 'fieldNumber' => 1],
                ],
            ],
        ];

        $output = $this->writer->write($service, $messages);

        expect($output)
            ->toContain('message GetUserRequest {')
            ->toContain('message GetUserResponse {');
    });

    it('emits field declarations with correct proto type, name, and field number', function () {
        $service = pw_makeService('User');
        $messages = [
            [
                'name'   => 'GetUserRequest',
                'fields' => [
                    ['name' => 'id',    'protoType' => 'int32',           'fieldNumber' => 1],
                    ['name' => 'email', 'protoType' => 'optional string', 'fieldNumber' => 2],
                ],
            ],
        ];

        $output = $this->writer->write($service, $messages);

        expect($output)
            ->toContain('  int32 id = 1;')
            ->toContain('  optional string email = 2;');
    });

    it('emits repeated fields correctly', function () {
        $service = pw_makeService('Order');
        $messages = [
            [
                'name'   => 'ListOrdersResponse',
                'fields' => [
                    ['name' => 'items', 'protoType' => 'repeated string', 'fieldNumber' => 1],
                ],
            ],
        ];

        $output = $this->writer->write($service, $messages);

        expect($output)->toContain('  repeated string items = 1;');
    });

    it('emits an empty message block when there are no fields', function () {
        $service = pw_makeService('User');
        $messages = [
            ['name' => 'EmptyRequest', 'fields' => []],
        ];

        $output = $this->writer->write($service, $messages);

        expect($output)->toContain("message EmptyRequest {\n}");
    });

    // ── Nested messages ───────────────────────────────────────────────────────

    it('emits nested message definitions inside the parent message', function () {
        $service = pw_makeService('User');
        $messages = [
            [
                'name'   => 'GetUserResponse',
                'fields' => [
                    ['name' => 'address', 'protoType' => 'Address', 'fieldNumber' => 1],
                ],
                'nested' => [
                    [
                        'name'   => 'Address',
                        'fields' => [
                            ['name' => 'street', 'protoType' => 'string', 'fieldNumber' => 1],
                            ['name' => 'city',   'protoType' => 'string', 'fieldNumber' => 2],
                        ],
                    ],
                ],
            ],
        ];

        $output = $this->writer->write($service, $messages);

        expect($output)
            ->toContain('message GetUserResponse {')
            ->toContain('  message Address {')
            ->toContain('    string street = 1;')
            ->toContain('    string city = 2;')
            ->toContain('  Address address = 1;');
    });

    // ── Full output structure ─────────────────────────────────────────────────

    it('produces a complete valid proto3 file matching the expected format', function () {
        $service = pw_makeService('User', 'mypackage', [
            pw_makeHandler('GetUser', 'GetUserRequest', 'GetUserResponse'),
        ]);

        $messages = [
            [
                'name'   => 'GetUserRequest',
                'fields' => [
                    ['name' => 'id', 'protoType' => 'int32', 'fieldNumber' => 1],
                ],
            ],
            [
                'name'   => 'GetUserResponse',
                'fields' => [
                    ['name' => 'id',   'protoType' => 'int32',  'fieldNumber' => 1],
                    ['name' => 'name', 'protoType' => 'string', 'fieldNumber' => 2],
                ],
            ],
        ];

        $expected = <<<'PROTO'
syntax = "proto3";

package mypackage;

service User {
  rpc GetUser (GetUserRequest) returns (GetUserResponse);
}

message GetUserRequest {
  int32 id = 1;
}

message GetUserResponse {
  int32 id = 1;
  string name = 2;
}
PROTO;

        // Normalise line endings so the test passes on both Windows and Unix.
        $expected = str_replace("\r\n", "\n", $expected) . "\n";
        $actual   = str_replace("\r\n", "\n", $this->writer->write($service, $messages));

        expect($actual)->toBe($expected);
    });

    it('produces identical output on repeated calls with the same input (idempotency)', function () {
        $service = pw_makeService('User', 'mypackage', [
            pw_makeHandler('GetUser', 'GetUserRequest', 'GetUserResponse'),
        ]);

        $messages = [
            [
                'name'   => 'GetUserRequest',
                'fields' => [
                    ['name' => 'id', 'protoType' => 'int32', 'fieldNumber' => 1],
                ],
            ],
        ];

        $first  = $this->writer->write($service, $messages);
        $second = $this->writer->write($service, $messages);

        expect($first)->toBe($second);
    });

    it('ends the output with a trailing newline', function () {
        $service = pw_makeService('User');
        $output = $this->writer->write($service, []);

        expect(str_ends_with($output, "\n"))->toBeTrue();
    });

    // ── Multiple handlers ─────────────────────────────────────────────────────

    it('emits all rpc entries when a service has multiple handlers', function () {
        $service = pw_makeService('Product', 'shop', [
            pw_makeHandler('CreateProduct', 'CreateProductRequest', 'CreateProductResponse'),
            pw_makeHandler('GetProduct',    'GetProductRequest',    'GetProductResponse'),
            pw_makeHandler('DeleteProduct', 'DeleteProductRequest', 'DeleteProductResponse'),
        ]);

        $output = $this->writer->write($service, []);

        expect($output)
            ->toContain('rpc CreateProduct (CreateProductRequest) returns (CreateProductResponse);')
            ->toContain('rpc GetProduct (GetProductRequest) returns (GetProductResponse);')
            ->toContain('rpc DeleteProduct (DeleteProductRequest) returns (DeleteProductResponse);');
    });

    // ── All scalar proto types ────────────────────────────────────────────────

    it('emits all proto3 scalar types correctly', function () {
        $service = pw_makeService('Scalar');
        $messages = [
            [
                'name'   => 'ScalarMessage',
                'fields' => [
                    ['name' => 'str_field',   'protoType' => 'string', 'fieldNumber' => 1],
                    ['name' => 'int_field',   'protoType' => 'int32',  'fieldNumber' => 2],
                    ['name' => 'float_field', 'protoType' => 'float',  'fieldNumber' => 3],
                    ['name' => 'bool_field',  'protoType' => 'bool',   'fieldNumber' => 4],
                ],
            ],
        ];

        $output = $this->writer->write($service, $messages);

        expect($output)
            ->toContain('  string str_field = 1;')
            ->toContain('  int32 int_field = 2;')
            ->toContain('  float float_field = 3;')
            ->toContain('  bool bool_field = 4;');
    });
});
