<?php

use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\NormalizeCurrencyInput;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            NormalizeCurrencyInput::class,
        ]);
        $middleware->alias([
            'role' => EnsureUserRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sesi Anda telah diperbarui. Muat ulang halaman lalu coba lagi.',
                ], 419);
            }

            $message = 'Sesi Anda sempat berakhir. Silakan ulangi tindakan terakhir; data yang sudah tersimpan tetap aman.';

            return $request->user()
                ? redirect()->back()->with('error', $message)
                : redirect()->route('login')->with('error', $message);
        });
    })->create();
