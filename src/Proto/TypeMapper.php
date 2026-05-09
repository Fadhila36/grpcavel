<?php

declare(strict_types=1);

namespace Grpcavel\Proto;

/**
 * Maps PHP types to proto3 type strings.
 *
 * Handles scalar types, nullable (optional) types, repeated (array) fields,
 * and nested DTO class references.
 */
final class TypeMapper
{
    /**
     * PHP scalar type → proto3 scalar type mapping.
     *
     * @var array<string, string>
     */
    private const SCALAR_MAP = [
        'string' => 'string',
        'int'    => 'int32',
        'float'  => 'float',
        'bool'   => 'bool',
    ];

    /**
     * Convert a PHP type to its proto3 representation.
     *
     * @param  string       $phpType      The PHP type name (e.g. 'string', 'int', 'App\Dto\UserDto').
     * @param  bool         $nullable     Whether the field is nullable (emits `optional {type}`).
     * @param  bool         $repeated     Whether the field is a repeated array field.
     * @param  string|null  $repeatedType The element type when $repeated is true (e.g. 'string', 'UserDto').
     */
    public function toProto3(
        string $phpType,
        bool $nullable = false,
        bool $repeated = false,
        ?string $repeatedType = null,
    ): string {
        if ($repeated) {
            $elementProtoType = $this->resolveType($repeatedType ?? 'string');

            return "repeated {$elementProtoType}";
        }

        $protoType = $this->resolveType($phpType);

        if ($nullable) {
            return "optional {$protoType}";
        }

        return $protoType;
    }

    /**
     * Resolve a single PHP type to its proto3 equivalent.
     *
     * Scalar types are mapped via the lookup table. Class references (fully-qualified
     * or short names starting with an uppercase letter) are reduced to their short name.
     */
    private function resolveType(string $phpType): string
    {
        if (isset(self::SCALAR_MAP[$phpType])) {
            return self::SCALAR_MAP[$phpType];
        }

        // Fully-qualified class name (contains backslash) or short class name
        // (starts with uppercase) → use the short name as a message reference.
        if (str_contains($phpType, '\\') || (strlen($phpType) > 0 && ctype_upper($phpType[0]))) {
            return $this->shortClassName($phpType);
        }

        // Unknown type — return as-is and let the caller/compiler handle it.
        return $phpType;
    }

    /**
     * Extract the short class name from a fully-qualified class name.
     *
     * e.g. 'App\Grpc\Dto\UserDto' → 'UserDto'
     */
    private function shortClassName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }
}
