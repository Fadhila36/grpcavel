<?php

declare(strict_types=1);

namespace Grpcavel\Tests\Unit\Validation;

use Grpcavel\Contracts\GrpcRequest;
use Grpcavel\Validation\RequestValidator;
use Grpcavel\Tests\TestCase;
use Illuminate\Validation\ValidationException;

class RequestValidatorTest extends TestCase
{
    private RequestValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new RequestValidator();
    }

    public function test_it_skips_validation_if_dto_does_not_implement_grpcrequest(): void
    {
        $dto = new class {
            public string $name = 'test';
        };

        $this->validator->validate($dto);
        $this->assertTrue(true); // Should not throw
    }

    public function test_it_throws_validation_exception_on_failure(): void
    {
        $dto = new class implements GrpcRequest {
            public string $email = 'not-an-email';

            public function rules(): array { return ['email' => 'required|email']; }
            public function messages(): array { return []; }
            public function attributes(): array { return []; }
        };

        $this->expectException(ValidationException::class);
        $this->validator->validate($dto);
    }

    public function test_it_passes_validation_on_success(): void
    {
        $dto = new class implements GrpcRequest {
            public string $email = 'test@example.com';

            public function rules(): array { return ['email' => 'required|email']; }
            public function messages(): array { return []; }
            public function attributes(): array { return []; }
        };

        $this->validator->validate($dto);
        $this->assertTrue(true); // Should not throw
    }
}
