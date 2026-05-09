<?php

declare(strict_types=1);

namespace Grpcavel\Runtime;

use Grpcavel\Contracts\ExceptionMapperContract;
use Grpcavel\Contracts\MiddlewarePipelineContract;
use Grpcavel\Contracts\RequestDispatcherContract;
use Grpcavel\Contracts\SerializerContract;
use Grpcavel\Contracts\ValidatorContract;
use Grpcavel\Discovery\ServiceDefinition;
use Grpcavel\Runtime\GrpcStatus;
use Grpcavel\Http\GrpcStatusResponse;
use Throwable;

final class RequestDispatcher implements RequestDispatcherContract
{
    public function __construct(
        private readonly MiddlewarePipelineContract $middleware,
        private readonly ValidatorContract $validator,
        private readonly SerializerContract $serializer,
        private readonly ExceptionMapperContract $exceptionMapper,
    ) {}

    public function dispatch(
        ServiceDefinition $service,
        string $method,
        mixed $body,
        array $context
    ): mixed {
        try {
            $handler = null;
            foreach ($service->handlers as $h) {
                if ($h->rpcName === $method || $h->methodName === $method) {
                    $handler = $h;
                    break;
                }
            }

            if (! $handler) {
                return $this->errorResponse('UNIMPLEMENTED', "Method $method not found in service {$service->serviceName}");
            }

            $requestDto = $body;

            if (is_string($body)) {
                $requestClass = $handler->requestClass;
                $reflection = new \ReflectionClass($requestClass);
                $requestDto = $reflection->newInstanceWithoutConstructor();
                
                if (method_exists($requestDto, 'mergeFromString')) {
                    $requestDto->mergeFromString($body);
                }
            }

            $this->validator->validate($requestDto);

            $middlewareStack = array_merge(
                $service->middlewareClasses,
                $handler->middlewareClasses
            );

            $responseDto = $this->middleware->run(
                $middlewareStack,
                $requestDto,
                function ($req) use ($service, $handler) {
                    return app($service->className)->{$handler->methodName}($req);
                }
            );

            if (is_string($body) && method_exists($responseDto, 'serializeToString')) {
                return $responseDto->serializeToString();
            }

            return $responseDto; 

        } catch (\Spiral\RoadRunner\GRPC\Exception\GRPCException $e) {
            throw $e;
        } catch (Throwable $e) {
            $status = $this->exceptionMapper->map($e);
            return $this->errorResponse($status->statusCode, $status->message);
        }
    }

    private function errorResponse(string $code, string $message): string
    {
        throw new \Spiral\RoadRunner\GRPC\Exception\GRPCException(
            $message,
            $this->mapStatusToCode($code)
        );
    }

    private function mapStatusToCode(string $status): int
    {
        return match ($status) {
            'OK' => GrpcStatus::OK,
            'CANCELLED' => GrpcStatus::CANCELLED,
            'UNKNOWN' => GrpcStatus::UNKNOWN,
            'INVALID_ARGUMENT' => GrpcStatus::INVALID_ARGUMENT,
            'DEADLINE_EXCEEDED' => GrpcStatus::DEADLINE_EXCEEDED,
            'NOT_FOUND' => GrpcStatus::NOT_FOUND,
            'ALREADY_EXISTS' => GrpcStatus::ALREADY_EXISTS,
            'PERMISSION_DENIED' => GrpcStatus::PERMISSION_DENIED,
            'RESOURCE_EXHAUSTED' => GrpcStatus::RESOURCE_EXHAUSTED,
            'FAILED_PRECONDITION' => GrpcStatus::FAILED_PRECONDITION,
            'ABORTED' => GrpcStatus::ABORTED,
            'OUT_OF_RANGE' => GrpcStatus::OUT_OF_RANGE,
            'UNIMPLEMENTED' => GrpcStatus::UNIMPLEMENTED,
            'INTERNAL' => GrpcStatus::INTERNAL,
            'UNAVAILABLE' => GrpcStatus::UNAVAILABLE,
            'DATA_LOSS' => GrpcStatus::DATA_LOSS,
            'UNAUTHENTICATED' => GrpcStatus::UNAUTHENTICATED,
            default => GrpcStatus::UNKNOWN,
        };
    }
}
