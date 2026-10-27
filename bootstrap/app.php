<?php

use App\Exceptions\QuotaExceeded;
use App\Exceptions\StorageUnavailable;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\RequireRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(SecurityHeaders::class);
        $middleware->trustHosts(at: fn (): array => config('security.trusted_hosts'), subdomains: false);
        $middleware->trustProxies(headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT);
        $middleware->alias(['role' => RequireRole::class]);
        $middleware->web(append: [AuthenticateSession::class, EnsureAccountActive::class]);
        $middleware->redirectUsersTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (QuotaExceeded $exception, Request $request): Response {
            return $request->expectsJson()
                ? response()->json(['message' => __('files.quota_exceeded'), 'errors' => ['file' => [__('files.quota_exceeded')]]], 422)
                : redirect()->route('files.index')->withErrors(['file' => __('files.quota_exceeded')]);
        });
        $exceptions->render(function (StorageUnavailable $exception, Request $request): Response {
            return $request->expectsJson()
                ? response()->json(['message' => __('files.storage_unavailable')], 503)
                : response()->view('errors.503', [], 503);
        });
        $exceptions->dontFlash(['token']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
