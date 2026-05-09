<?php

declare(strict_types=1);

namespace Grpcavel\Tests\Unit\Exceptions;

use Grpcavel\Exceptions\ExceptionMapper;
use Grpcavel\Tests\TestCase;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Exception;

class ExceptionMapperTest extends TestCase
{
    public function test_it_maps_builtin_exceptions(): void
    {
        $mapper = new ExceptionMapper();
        
        // ValidationException
        $v = Validator::make(['email' => 'bad'], ['email' => 'email']);
        $e = new ValidationException($v);
        $res = $mapper->map($e);
        $this->assertEquals('INVALID_ARGUMENT', $res->statusCode);
        $this->assertStringContainsString('email', $res->message);
    }

    public function test_it_maps_custom_exceptions(): void
    {
        $mapper = new ExceptionMapper([
            \InvalidArgumentException::class => 'ALREADY_EXISTS',
        ]);

        $e = new \InvalidArgumentException('Already here');
        $res = $mapper->map($e);
        $this->assertEquals('ALREADY_EXISTS', $res->statusCode);
        $this->assertEquals('Already here', $res->message);
    }

    public function test_it_defaults_to_internal_for_unknown_exceptions(): void
    {
        $mapper = new ExceptionMapper();
        
        $e = new Exception('Secret info');
        $res = $mapper->map($e);
        
        $this->assertEquals('INTERNAL', $res->statusCode);
        $this->assertEquals('An internal error occurred.', $res->message);
    }

    public function test_it_can_register_new_mappings(): void
    {
        $mapper = new ExceptionMapper();
        $mapper->register(\RuntimeException::class, 'UNAVAILABLE');
        
        $e = new \RuntimeException('Down');
        $res = $mapper->map($e);
        $this->assertEquals('UNAVAILABLE', $res->statusCode);
    }
}
