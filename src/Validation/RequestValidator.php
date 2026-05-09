<?php

declare(strict_types=1);

namespace Grpcavel\Validation;

use Grpcavel\Contracts\GrpcRequest;
use Grpcavel\Contracts\ValidatorContract;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class RequestValidator implements ValidatorContract
{
    /**
     * Validate the given DTO if it supports validation.
     *
     * @param  object  $dto
     * @throws ValidationException
     */
    public function validate(object $dto): void
    {
        if (! $dto instanceof GrpcRequest) {
            return;
        }

        $rules = $dto->rules();

        if (empty($rules)) {
            return;
        }

        $data = $this->extractData($dto);

        $validator = Validator::make(
            data: $data,
            rules: $rules,
            messages: $dto->messages(),
            attributes: $dto->attributes()
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Extract public properties from the DTO.
     *
     * @return array<string, mixed>
     */
    private function extractData(object $dto): array
    {
        return get_object_vars($dto);
    }
}
