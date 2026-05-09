<?php

declare(strict_types=1);

use Grpcavel\Exceptions\ExceptionMapper;

// ---------------------------------------------------------------------------
// Property 10: Exception Mapper Always Returns INTERNAL for Unknown Exceptions
// ---------------------------------------------------------------------------

it('always returns INTERNAL for unknown exceptions and sanitizes the message', function (string $msg) {
    $mapper = new ExceptionMapper();
    $e = new Exception($msg);
    
    $res = $mapper->map($e);
    
    expect($res->statusCode)->toBe('INTERNAL');
    expect($res->message)->toBe('An internal error occurred.');
    expect($res->message)->not->toContain($msg);
})->with([
    'Secret database error',
    'Unexpected null pointer',
    'Connection to 10.0.0.1 failed',
])->group('Feature: exceptions, Property 10: Unknown Exceptions');

// ---------------------------------------------------------------------------
// Property 11: Custom Mappings Take Precedence
// ---------------------------------------------------------------------------

it('respects custom exception mappings', function (string $exceptionClass, string $statusCode) {
    $mapper = new ExceptionMapper([
        $exceptionClass => $statusCode
    ]);
    
    $e = new $exceptionClass("Test error");
    $res = $mapper->map($e);
    
    expect($res->statusCode)->toBe($statusCode);
})->with([
    [\RuntimeException::class, 'RESOURCE_EXHAUSTED'],
    [\LogicException::class, 'FAILED_PRECONDITION'],
    [\InvalidArgumentException::class, 'OUT_OF_RANGE'],
])->group('Feature: exceptions, Property 11: Custom Mappings');
