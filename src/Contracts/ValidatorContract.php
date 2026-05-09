<?php

declare(strict_types=1);

namespace Grpcavel\Contracts;

/**
 * Contract for validating incoming gRPC request DTOs.
 */
interface ValidatorContract
{
    /**
     * Validate the given request DTO against its declared rules.
     *
     * If the DTO does not extend GrpcRequest or defines no rules, this method
     * returns immediately without throwing. If validation fails, a
     * \Illuminate\Validation\ValidationException is thrown.
     *
     * @param  object  $dto  The deserialized request DTO to validate
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(object $dto): void;
}
