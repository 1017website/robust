<?php

use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\NormalizeCurrencyInput;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($exception->getStatusCode() !== 419 || ! $exception->getPrevious() instanceof TokenMismatchException) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sesi tidak cocok atau telah berakhir. Muat ulang halaman lalu coba lagi.',
                ], 419);
            }

            $message = 'Sesi tidak cocok atau telah berakhir. Silakan coba lagi dari halaman yang baru dimuat.';

            if ($request->is('login')) {
                return redirect()->route('login')
                    ->with('error', $message)
                    ->withInput($request->only('email'));
            }

            return $request->user()
                ? redirect()->back()->with('error', $message)
                : redirect()->route('login')->with('error', $message);
        });
    })->create();
