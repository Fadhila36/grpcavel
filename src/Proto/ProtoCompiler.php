<?php

declare(strict_types=1);

namespace Grpcavel\Proto;

use Grpcavel\Contracts\ProtoCompilerContract;
use Grpcavel\Discovery\ServiceDefinition;
use Grpcavel\Attributes\Repeated;
use ReflectionClass;
use ReflectionProperty;

final class ProtoCompiler implements ProtoCompilerContract
{
    public function __construct(
        private readonly TypeMapper $typeMapper,
        private readonly ProtoWriter $protoWriter,
        private readonly string $protoPath,
    ) {}

    /**
     * @param array<int, ServiceDefinition> $services
     * @return array<int, string>
     */
    public function compile(array $services): array
    {
        $this->ensureProtoDirectoryExists();

        $generatedPaths = [];

        foreach ($services as $service) {
            $definitions = $this->discoverMessageDefinitions($service);
            $content = $this->protoWriter->write($service, $definitions);
            
            $path = $this->protoPath . DIRECTORY_SEPARATOR . $service->serviceName . '.proto';
            file_put_contents($path, $content);
            
            $generatedPaths[] = $path;
        }

        return $generatedPaths;
    }

    private function ensureProtoDirectoryExists(): void
    {
        if (! is_dir($this->protoPath)) {
            mkdir($this->protoPath, 0755, true);
        }
    }

    private function discoverMessageDefinitions(ServiceDefinition $service): array
    {
        $definitions = [];
        $processedClasses = [];

        foreach ($service->handlers as $handler) {
            $this->extractMessageDefinition($handler->requestClass, $definitions, $processedClasses);
            $this->extractMessageDefinition($handler->responseClass, $definitions, $processedClasses);
        }

        return $definitions;
    }

    /**
     * @param array<string, mixed> $definitions
     * @param array<int, string> $processedClasses
     */
    private function extractMessageDefinition(string $className, array &$definitions, array &$processedClasses): void
    {
        if (in_array($className, $processedClasses)) {
            return;
        }

        $processedClasses[] = $className;
        
        /** @var class-string $className */
        $reflection = new ReflectionClass($className);
        $shortName = $reflection->getShortName();

        $fields = [];
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $metadata = $this->extractFieldMetadata($property);
            if ($metadata === null) {
                continue;
            }

            $fields[] = [
                'name' => $property->getName(),
                'protoType' => $metadata['protoType'],
                'fieldNumber' => count($fields) + 1,
            ];

            if ($metadata['shouldRecurse']) {
                $this->extractMessageDefinition($metadata['elementType'], $definitions, $processedClasses);
            }
        }

        $definitions[$shortName] = [
            'name' => $shortName,
            'fields' => $fields,
        ];
    }

    /**
     * @return array{protoType: string, elementType: string, shouldRecurse: bool}|null
     */
    private function extractFieldMetadata(ReflectionProperty $property): ?array
    {
        $type = $property->getType();
        if ($type === null) {
            return null;
        }

        $phpType = $type instanceof \ReflectionNamedType ? $type->getName() : 'string';
        $nullable = $type->allowsNull();
        $repeatedAttr = $property->getAttributes(Repeated::class);
        $isRepeated = $repeatedAttr !== [];
        
        $elementType = $phpType;
        if ($isRepeated) {
            /** @var Repeated $instance */
            $instance = $repeatedAttr[0]->newInstance();
            $elementType = $instance->type;
        }

        $shouldRecurse = class_exists($elementType) && ! $this->isBuiltinType($elementType);
        $protoType = $this->typeMapper->toProto3($elementType, $nullable, $isRepeated);

        return [
            'protoType' => $protoType,
            'elementType' => $elementType,
            'shouldRecurse' => $shouldRecurse,
        ];
    }

    private function isBuiltinType(string $type): bool
    {
        return in_array($type, ['string', 'int', 'float', 'bool', 'array', 'mixed', 'void']);
    }
}
