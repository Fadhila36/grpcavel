<?php

declare(strict_types=1);

namespace Grpcavel\Tests\Unit\Serialization;

use Grpcavel\Serialization\ModelSerializer;
use Grpcavel\Contracts\SerializerContract;
use Grpcavel\Tests\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Arrayable;

class ModelSerializerTest extends TestCase
{
    public function test_it_serializes_plain_objects(): void
    {
        $serializer = new ModelSerializer();
        $dto = new class {
            public string $name = 'test';
            public int $id = 1;
        };

        $res = $serializer->serialize($dto);
        $this->assertEquals(['name' => 'test', 'id' => 1], $res);
    }

    public function test_it_serializes_nested_objects(): void
    {
        $serializer = new ModelSerializer();
        $child = new class { public string $v = 'child'; };
        $parent = new class($child) {
            public function __construct(public $child) {}
        };

        $res = $serializer->serialize($parent);
        $this->assertEquals(['child' => ['v' => 'child']], $res);
    }

    public function test_it_serializes_collections_and_arrayables(): void
    {
        $serializer = new ModelSerializer();
        
        $arrayable = new class implements Arrayable {
            public function toArray() { return ['a' => 1]; }
        };
        
        $collection = collect([$arrayable, ['b' => 2]]);
        
        $dto = new class($collection) {
            public function __construct(public $items) {}
        };

        $res = $serializer->serialize($dto);
        
        $this->assertEquals([
            'items' => [
                ['a' => 1],
                ['b' => 2],
            ]
        ], $res);
    }

    public function test_grpc_helper_delegates_to_serializer(): void
    {
        $model = new class extends \Illuminate\Database\Eloquent\Model {
            protected $attributes = ['id' => 123, 'name' => 'Helper'];
        };

        $this->app->instance(SerializerContract::class, new ModelSerializer());

        $dtoClass = get_class(new class { public int $id; public string $name; });

        $dto = grpc($model, $dtoClass);

        $this->assertEquals(123, $dto->id);
        $this->assertEquals('Helper', $dto->name);
    }
}
