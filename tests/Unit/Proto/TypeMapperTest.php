<?php

declare(strict_types=1);

use Grpcavel\Proto\TypeMapper;

describe('TypeMapper', function () {
    beforeEach(function () {
        $this->mapper = new TypeMapper();
    });

    // -------------------------------------------------------------------------
    // Scalar type mapping (Requirement 4.4)
    // -------------------------------------------------------------------------

    it('maps string to proto3 string', function () {
        expect($this->mapper->toProto3('string'))->toBe('string');
    });

    it('maps int to proto3 int32', function () {
        expect($this->mapper->toProto3('int'))->toBe('int32');
    });

    it('maps float to proto3 float', function () {
        expect($this->mapper->toProto3('float'))->toBe('float');
    });

    it('maps bool to proto3 bool', function () {
        expect($this->mapper->toProto3('bool'))->toBe('bool');
    });

    // -------------------------------------------------------------------------
    // Nullable types emit `optional {type}` (Requirement 4.8)
    // -------------------------------------------------------------------------

    it('emits optional string for nullable string', function () {
        expect($this->mapper->toProto3('string', nullable: true))->toBe('optional string');
    });

    it('emits optional int32 for nullable int', function () {
        expect($this->mapper->toProto3('int', nullable: true))->toBe('optional int32');
    });

    it('emits optional float for nullable float', function () {
        expect($this->mapper->toProto3('float', nullable: true))->toBe('optional float');
    });

    it('emits optional bool for nullable bool', function () {
        expect($this->mapper->toProto3('bool', nullable: true))->toBe('optional bool');
    });

    it('emits optional message reference for nullable DTO class', function () {
        expect($this->mapper->toProto3('App\\Grpc\\Dto\\UserDto', nullable: true))->toBe('optional UserDto');
    });

    // -------------------------------------------------------------------------
    // Repeated fields (Requirement 4.7)
    // -------------------------------------------------------------------------

    it('emits repeated string for array with string element type', function () {
        expect($this->mapper->toProto3('array', repeated: true, repeatedType: 'string'))->toBe('repeated string');
    });

    it('emits repeated int32 for array with int element type', function () {
        expect($this->mapper->toProto3('array', repeated: true, repeatedType: 'int'))->toBe('repeated int32');
    });

    it('emits repeated float for array with float element type', function () {
        expect($this->mapper->toProto3('array', repeated: true, repeatedType: 'float'))->toBe('repeated float');
    });

    it('emits repeated bool for array with bool element type', function () {
        expect($this->mapper->toProto3('array', repeated: true, repeatedType: 'bool'))->toBe('repeated bool');
    });

    it('emits repeated message reference for array with DTO element type (short name)', function () {
        expect($this->mapper->toProto3('array', repeated: true, repeatedType: 'UserDto'))->toBe('repeated UserDto');
    });

    it('emits repeated message reference for array with fully-qualified DTO element type', function () {
        expect($this->mapper->toProto3('array', repeated: true, repeatedType: 'App\\Grpc\\Dto\\UserDto'))->toBe('repeated UserDto');
    });

    it('defaults repeated element type to string when repeatedType is null', function () {
        expect($this->mapper->toProto3('array', repeated: true))->toBe('repeated string');
    });

    // -------------------------------------------------------------------------
    // Nested DTO class references (Requirement 4.6)
    // -------------------------------------------------------------------------

    it('maps a short DTO class name to a message reference', function () {
        expect($this->mapper->toProto3('UserDto'))->toBe('UserDto');
    });

    it('maps a fully-qualified class name to its short name as a message reference', function () {
        expect($this->mapper->toProto3('App\\Grpc\\Dto\\UserDto'))->toBe('UserDto');
    });

    it('maps a deeply nested fully-qualified class name to its short name', function () {
        expect($this->mapper->toProto3('App\\Domain\\Orders\\Dto\\OrderItemDto'))->toBe('OrderItemDto');
    });

    // -------------------------------------------------------------------------
    // Non-nullable, non-repeated scalars (default parameter behaviour)
    // -------------------------------------------------------------------------

    it('does not add optional prefix when nullable is false', function () {
        expect($this->mapper->toProto3('string', nullable: false))->toBe('string');
    });

    it('does not add repeated prefix when repeated is false', function () {
        expect($this->mapper->toProto3('string', repeated: false))->toBe('string');
    });

    // -------------------------------------------------------------------------
    // repeated takes precedence over nullable
    // -------------------------------------------------------------------------

    it('emits repeated (not optional) when both repeated and nullable are true', function () {
        // A repeated field cannot be optional in proto3; repeated wins.
        expect($this->mapper->toProto3('array', nullable: true, repeated: true, repeatedType: 'string'))
            ->toBe('repeated string');
    });
});
