<?php

declare(strict_types=1);

use Grpcavel\Contracts\GrpcRequest;
use Grpcavel\Validation\RequestValidator;
use Illuminate\Validation\ValidationException;

// ---------------------------------------------------------------------------
// Property 8: Validation Always Throws on Failure
// ---------------------------------------------------------------------------

it('always throws ValidationException when rules are violated', function (string $invalidEmail) {
    $validator = new RequestValidator();
    
    $dto = new class($invalidEmail) implements GrpcRequest {
        public function __construct(public string $email) {}
        public function rules(): array { return ['email' => 'required|email']; }
        public function messages(): array { return []; }
        public function attributes(): array { return []; }
    };

    expect(fn() => $validator->validate($dto))->toThrow(ValidationException::class);
})->with([
    'not-an-email',
    'missing-at-domain.com',
    '@no-user.com',
    'user@',
])->group('Feature: validation, Property 8: Validation Failure');

// ---------------------------------------------------------------------------
// Property 9: Validated Request Passes Through Unchanged
// ---------------------------------------------------------------------------

it('passes through valid request data unchanged', function (string $validEmail, int $age) {
    $validator = new RequestValidator();
    
    $dto = new class($validEmail, $age) implements GrpcRequest {
        public function __construct(public string $email, public int $age) {}
        public function rules(): array { return ['email' => 'required|email', 'age' => 'integer|min:18']; }
        public function messages(): array { return []; }
        public function attributes(): array { return []; }
    };

    $validator->validate($dto);
    
    expect($dto->email)->toBe($validEmail);
    expect($dto->age)->toBe($age);
})->with([
    ['alice@example.com', 25],
    ['bob@work.co', 40],
    ['charlie@test.io', 18],
])->group('Feature: validation, Property 9: Validation Success');
