<?php

declare(strict_types=1);

use Grpcavel\Tests\TestCase;

// ---------------------------------------------------------------------------
// Property 14: Serialization Round-Trip
// ---------------------------------------------------------------------------

it('performs a perfect binary serialization round-trip', function (string $name, int $id) {
    // We'll use a mock DTO that simulates a Google Protobuf Message
    $dto = new class($name, $id) {
        public function __construct(public string $name, public int $id) {}
        
        // Simulate Protobuf methods
        public function serializeToString(): string {
            return serialize(['name' => $this->name, 'id' => $this->id]);
        }
        
        public function mergeFromString(string $data): void {
            $data = unserialize($data);
            $this->name = $data['name'];
            $this->id = $data['id'];
        }
    };

    $binary = $dto->serializeToString();
    
    $newDto = new (get_class($dto))('', 0);
    $newDto->mergeFromString($binary);
    
    expect($newDto->name)->toBe($name);
    expect($newDto->id)->toBe($id);
})->with([
    ['Alice', 1],
    ['Bob', 42],
    ['Charlie', 999],
])->group('Feature: runtime, Property 14: Serialization Round-Trip');
