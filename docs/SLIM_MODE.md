# Slim Mode (Optimasi Performa gRPC Worker)

Saat menjalankan Laravel sebagai gRPC Worker (`php artisan grpc:worker`), aplikasi Anda berjalan sebagai proses panjang (*persistent process*) di latar belakang. Karena gRPC hanya digunakan untuk komunikasi antar server (RPC), Anda tidak memerlukan fitur-fitur web tradisional seperti Session, Cookies, Views, atau Blade.

Menonaktifkan fitur-fitur ini pada proses gRPC Worker dapat menghemat penggunaan memori (RAM) dan CPU secara signifikan, membuat Laravel Anda seringan framework mikro.

## Cara Implementasi

Karena mengutak-atik *Service Provider* bawaan Laravel secara otomatis sangat berisiko, kami menyarankan Anda untuk menerapkannya secara manual di aplikasi Anda.

### Untuk Laravel 11+

Pada Laravel 11, *Service Provider* diatur di `bootstrap/providers.php` dan konfigurasi aplikasi diatur di `bootstrap/app.php`.

Anda bisa menambahkan logika deteksi di `bootstrap/app.php` untuk memodifikasi konfigurasi sebelum aplikasi di-boot:

```php
// bootstrap/app.php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->booting(function () {
        // Deteksi apakah sedang berjalan sebagai gRPC Worker
        if (app()->runningInConsole() && str_contains(implode(' ', $_SERVER['argv']), 'grpc:worker')) {
            
            // Nonaktifkan provider yang tidak diperlukan
            $disabledProviders = [
                Illuminate\Session\SessionServiceProvider::class,
                Illuminate\Cookie\CookieServiceProvider::class,
                Illuminate\View\ViewServiceProvider::class,
                Illuminate\Pagination\PaginationServiceProvider::class,
            ];
            
            $providers = config('app.providers');
            
            if (is_array($providers)) {
                config(['app.providers' => array_diff($providers, $disabledProviders)]);
            }
        }
    })
    ->create();
```

### Untuk Laravel 10 dan sebelumnya

Tambahkan logika berikut di bagian atas metode `register` pada `app/Providers/AppServiceProvider.php`:

```php
public function register(): void
{
    if ($this->app->runningInConsole() && str_contains(implode(' ', $_SERVER['argv']), 'grpc:worker')) {
        
        $disabledProviders = [
            \Illuminate\Session\SessionServiceProvider::class,
            \Illuminate\Cookie\CookieServiceProvider::class,
            \Illuminate\View\ViewServiceProvider::class,
            \Illuminate\Pagination\PaginationServiceProvider::class,
        ];
        
        $providers = config('app.providers');
        
        if (is_array($providers)) {
            config(['app.providers' => array_diff($providers, $disabledProviders)]);
        }
    }
}
```

## Hasil

Dengan menerapkan ini, proses gRPC Worker Anda akan menggunakan RAM yang lebih sedikit (bisa hemat 10-20MB per worker) dan waktu *bootstrap* awal yang lebih cepat. Ini sangat direkomendasikan untuk lingkungan produksi dengan trafik tinggi!
