# Grpcavel

<p align="center">
    <img src="art/logo.png" width="300" alt="Grpcavel Logo">
</p>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/fadhila36/grpcavel.svg?style=flat-square)](https://packagist.org/packages/fadhila36/grpcavel)
[![License](https://img.shields.io/packagist/l/fadhila36/grpcavel.svg?style=flat-square)](https://packagist.org/packages/fadhila36/grpcavel)
[![Tests](https://github.com/fadhila36/grpcavel/actions/workflows/tests.yml/badge.svg)](https://github.com/fadhila36/grpcavel/actions/workflows/tests.yml)


Grpcavel is a gRPC framework for Laravel that focuses on developer experience. It uses a code-first approach to generate and compile protobuf files, meaning you don't have to write `.proto` definitions manually.

## Why Grpcavel?

Building gRPC services in PHP is traditionally painful. Grpcavel makes it feel like building a standard Laravel API:

- **Code-First**: Your PHP classes are the source of truth. Protos are generated automatically.
- **RoadRunner Runtime**: Uses a persistent worker model for high performance.
- **Laravel Native**: Supports Laravel validation, middleware, and Eloquent.

## Compatibility

Grpcavel is tested against multiple Laravel and PHP versions to ensure stability:

| Laravel Version | PHP Version |
| --- | --- |
| **10.x** | 8.2, 8.3 |
| **11.x** | 8.2, 8.3, 8.4 |
| **12.x** | 8.2, 8.3, 8.4 |
| **13.x** | 8.3, 8.4 |

## Documentation

Full documentation is available at [github.com/Fadhila36/grpcavel-docs](https://github.com/Fadhila36/grpcavel-docs).

## Installation

You can install the package via composer:

```bash
composer require fadhila36/grpcavel
```

After installing, run the install command to prepare the environment:

```bash
php artisan grpc:install
```

## Usage

### 1. Create a Service
Generate a new service class using Artisan:

```bash
php artisan grpc:make-service UserService
```

### 2. Define Methods
Annotate your service methods with `#[GrpcMethod]`. Each method must accept one request DTO and return one response DTO.

```php
namespace App\Grpc\Services;

use Grpcavel\Attributes\GrpcService;
use Grpcavel\Attributes\GrpcMethod;
use App\Grpc\Requests\GetUserRequest;
use App\Grpc\Responses\UserResponse;

#[GrpcService]
class UserService
{
    #[GrpcMethod]
    public function getUser(GetUserRequest $request): UserResponse
    {
        $user = \App\Models\User::findOrFail($request->id);
        
        return UserResponse::fromModel($user);
    }
}
```

### 3. Validation
Define rules in your request DTO by extending `GrpcRequest`:

```php
class GetUserRequest extends GrpcRequest
{
    public function __construct(public readonly int $id) {}

    public function rules(): array
    {
        return ['id' => 'required|integer|exists:users,id'];
    }
}
```

### 4. Synchronization
Sync your PHP definitions with `.proto` files and compile the stubs:

```bash
php artisan grpc:sync
```

### 5. Start the Server
Start the RoadRunner gRPC server:

```bash
php artisan grpc:start
```

## Middleware

You can apply middleware to services or individual methods using attributes:

```php
#[GrpcService]
#[Middleware(Authenticate::class)]
class SecureService {
    #[GrpcMethod]
    #[Middleware(LogRequest::class)]
    public function handle(...) {}
}
```

## Enterprise Features

### Rate Limiting
Protect your services from abuse using the built-in `RateLimitMiddleware`. Configure limits in `config/grpc.php` and apply it to your services:

```php
#[GrpcService]
#[Middleware(\Grpcavel\Middleware\RateLimitMiddleware::class)]
class PublicService { ... }
```

### Docker Support
Deploy with confidence using the included production-optimized `Dockerfile`. It handles PHP 8.3, RoadRunner binaries, and necessary extensions out of the box.

### Client Scaffolding
Generate client-side wrappers to facilitate communication between microservices:

```bash
php artisan grpc:make-client UserServiceClient
```

## Testing

Grpcavel provides a `GrpcClient` to test your services in-process:

```php
$response = GrpcClient::call(UserService::class, 'getUser', new GetUserRequest(id: 1));

expect($response)->toBeInstanceOf(UserResponse::class);
```

## Production Optimization

### Service Discovery Cache
In production, you can cache your service definitions to avoid the overhead of scanning directories and using reflection:

```bash
php artisan grpc:cache
```

To clear the cache:

```bash
php artisan grpc:clear
```

### Runtime Stability
Grpcavel is hardened for long-lived processes:
- **Memory Management**: Automatically flushes query logs and triggers garbage collection after each request.
- **Database Resilience**: Detects and recovers lost database connections between requests.

## Commands Reference

| Command | Description |
| --- | --- |
| `grpc:install` | Bootstrap directories and config. |
| `grpc:sync` | Generate and compile proto files. |
| `grpc:compile` | Manually compile proto files. |
| `grpc:cache` | Create service discovery cache. |
| `grpc:clear` | Remove service discovery cache. |
| `grpc:start` | Start the gRPC server. |
| `grpc:make-service` | Create a new service. |
| `grpc:make-client` | Create a client wrapper. |
| `grpc:make-request` | Create a request DTO. |
| `grpc:make-response` | Create a response DTO. |

## Open Source & Contributing

Grpcavel is an open-source project, and we welcome contributions from the community! Whether it's reporting a bug, proposing a new feature, or submitting a Pull Request, your help is highly appreciated.

If you're interested in contributing, please feel free to fork the repository and submit a PR. For major changes, please open an issue first to discuss what you would like to change.

## Credits

- [Muhammad Fadhila Abiyyu Faris](https://github.com/Fadhila36)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
