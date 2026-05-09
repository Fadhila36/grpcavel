<?php

declare(strict_types=1);

use Grpcavel\Serialization\ModelSerializer;
use Illuminate\Support\Collection;

// ---------------------------------------------------------------------------
// Property 14: Deep Serialization Integrity
// ---------------------------------------------------------------------------

it('performs deep serialization of nested objects and arrays', function (array $data) {
    $serializer = new ModelSerializer();
    
    // Create a nested DO
    $dto = (object) [
        'name' => $data['name'],
        'meta' => (object) [
            'tags' => $data['tags'],
            'score' => $data['score']
        ],
        'items' => collect($data['items'])->map(fn($i) => (object) ['val' => $i])
    ];

    $res = $serializer->serialize($dto);
    
    expect($res['name'])->toBe($data['name']);
    expect($res['meta']['tags'])->toBe($data['tags']);
    expect($res['meta']['score'])->toBe($data['score']);
    expect($res['items'][0]['val'])->toBe($data['items'][0]);
})->with([
    [[
        'name' => 'Test',
        'tags' => ['a', 'b'],
        'score' => 100,
        'items' => [1, 2, 3]
    ]],
    [[
        'name' => 'Another',
        'tags' => [],
        'score' => 0,
        'items' => ['x']
    ]],
])->group('Feature: serialization, Property 14: Deep Serialization');
