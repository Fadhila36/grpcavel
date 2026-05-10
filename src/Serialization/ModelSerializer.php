<?php

declare(strict_types=1);

namespace Grpcavel\Serialization;

use Grpcavel\Contracts\SerializerContract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ReflectionClass;

final class ModelSerializer implements SerializerContract
{
    public function fromModel(Model $model, string $responseClass): object
    {
        $reflection = new ReflectionClass($responseClass);
        $dto = $reflection->newInstanceWithoutConstructor();
        $attributes = $model->getAttributes();

        foreach ($reflection->getProperties() as $property) {
            $name = $property->getName();
            if (array_key_exists($name, $attributes)) {
                $property->setValue($dto, $attributes[$name]);
            }
        }

        return $dto;
    }

    public function serialize(object $dto): array
    {
        $result = $this->serializeValue($dto);
        if (!is_array($result)) {
            throw new \RuntimeException('Serialization did not return an array');
        }
        return $result;
    }

    private function serializeValue(mixed $value): mixed
    {
        if ($value instanceof Collection) {
            return $this->serializeArray($value->all());
        }

        if ($value instanceof Arrayable) {
            return $this->serializeArray($value->toArray());
        }

        if (is_object($value)) {
            return $this->serializeArray(get_object_vars($value));
        }

        if (is_array($value)) {
            return $this->serializeArray($value);
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $array
     * @return array<array-key, mixed>
     */
    private function serializeArray(array $array): array
    {
        foreach ($array as $key => $value) {
            $array[$key] = $this->serializeValue($value);
        }

        return $array;
    }
}
