<?php

declare(strict_types=1);

namespace Grpcavel\Proto;

use Grpcavel\Discovery\ServiceDefinition;

/**
 * Renders a ServiceDefinition and its message definitions into a valid proto3 string.
 *
 * The output format is:
 *
 *   syntax = "proto3";
 *
 *   package mypackage;          ← omitted when package is empty
 *
 *   service UserService {
 *     rpc GetUser (GetUserRequest) returns (GetUserResponse);
 *   }
 *
 *   message GetUserRequest {
 *     int32 id = 1;
 *     optional string email = 2;
 *   }
 *
 *   message GetUserResponse {
 *     int32 id = 1;
 *     string name = 2;
 *   }
 *
 * Each message definition is an associative array with the shape:
 *
 *   [
 *     'name'   => 'GetUserRequest',
 *     'fields' => [
 *       ['name' => 'id',    'protoType' => 'int32',           'fieldNumber' => 1],
 *       ['name' => 'email', 'protoType' => 'optional string', 'fieldNumber' => 2],
 *     ],
 *     'nested' => [          ← optional: nested message definitions (same shape)
 *       [ 'name' => 'Address', 'fields' => [...] ],
 *     ],
 *   ]
 */
final class ProtoWriter
{
    /**
     * Render a complete proto3 file as a string.
     *
     * @param  ServiceDefinition                                                    $service            The service metadata.
     * @param  array<int, array{name: string, fields: array<int, array<string, mixed>>, nested?: array<int, array<string, mixed>>}>  $messageDefinitions  All message definitions to emit.
     */
    public function write(ServiceDefinition $service, array $messageDefinitions): string
    {
        $lines = [];

        // ── Header ────────────────────────────────────────────────────────────
        $lines[] = 'syntax = "proto3";';
        $lines[] = '';

        // ── Package (omit when empty) ─────────────────────────────────────────
        if ($service->package !== '') {
            $lines[] = "package {$service->package};";
            $lines[] = '';
        }

        // ── Service block ─────────────────────────────────────────────────────
        $lines[] = "service {$service->serviceName} {";

        foreach ($service->handlers as $handler) {
            $lines[] = "  rpc {$handler->rpcName} ({$this->shortName($handler->requestClass)}) returns ({$this->shortName($handler->responseClass)});";
        }

        $lines[] = '}';

        // ── Message blocks ────────────────────────────────────────────────────
        foreach ($messageDefinitions as $message) {
            $lines[] = '';
            $lines = array_merge($lines, $this->renderMessage($message, 0));
        }

        return implode("\n", $lines) . "\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Render a single message definition (possibly with nested messages) at the given indent level.
     *
     * @param  array{name: string, fields: array<int, array<string, mixed>>, nested?: array<int, array<string, mixed>>}  $message
     * @param  int  $indentLevel  Number of two-space indents to prepend to each line.
     * @return array<int, string>
     */
    private function renderMessage(array $message, int $indentLevel): array
    {
        $indent = str_repeat('  ', $indentLevel);
        $innerIndent = str_repeat('  ', $indentLevel + 1);

        $lines = [];
        $lines[] = "{$indent}message {$message['name']} {";

        // Nested message definitions come first (before fields), matching proto3 convention.
        foreach ($message['nested'] ?? [] as $nested) {
            /** @var array{name: string, fields: array<int, array<string, mixed>>, nested?: array<int, array<string, mixed>>} $nested */
            $nestedLines = $this->renderMessage($nested, $indentLevel + 1);
            $lines = array_merge($lines, $nestedLines);
            $lines[] = '';
        }

        // Field declarations.
        foreach ($message['fields'] as $field) {
            $protoType = $field['protoType'] ?? '';
            $name = $field['name'] ?? '';
            $fieldNumber = $field['fieldNumber'] ?? 0;

            if (!is_string($protoType) || !is_string($name) || !is_int($fieldNumber)) {
                throw new \RuntimeException('Invalid field definition in message ' . $message['name']);
            }

            $lines[] = "{$innerIndent}{$protoType} {$name} = {$fieldNumber};";
        }

        $lines[] = "{$indent}}";

        return $lines;
    }

    /**
     * Extract the short class name from a fully-qualified class name.
     *
     * e.g. 'App\Grpc\Requests\GetUserRequest' → 'GetUserRequest'
     * e.g. 'GetUserRequest'                   → 'GetUserRequest'
     */
    private function shortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }
}
