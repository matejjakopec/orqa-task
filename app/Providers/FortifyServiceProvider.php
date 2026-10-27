<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\AuthenticateActiveAccount;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Responses\PasswordResetLinkResponse;
use App\Support\AccountIdentifier;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

final class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Fortify::ignoreRoutes();
        $this->app->bind(FortifyLoginRequest::class, LoginRequest::class);
        $this->app->bind(FailedPasswordResetLinkRequestResponse::class, PasswordResetLinkResponse::class);
        $this->app->bind(SuccessfulPasswordResetLinkRequestResponse::class, PasswordResetLinkResponse::class);
    }

    public function boot(): void
    {
        Fortify::loginView(fn (): View => view('auth.login'));
        Fortify::requestPasswordResetLinkView(fn (): View => view('auth.forgot-password'));
        Fortify::resetPasswordView(function (Request $request): View {
            $email = $request->query('email', '');

            return view('auth.reset-password', [
                'token' => $request->route('token'),
                'email' => is_string($email) ? AccountIdentifier::normalize($email) : '',
            ]);
        });
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::authenticateThrough(fn (Request $request): array => [
            AuthenticateActiveAccount::class,
            PrepareAuthenticatedSession::class,
        ]);

        RateLimiter::for('login', function (Request $request): array {
            $username = $request->input('username');
            $identifier = is_string($username) ? AccountIdentifier::normalize($username) : '';

            return [
                Limit::perMinute(config('fortify.login_limits.username'))->by('username:'.hash('sha256', $identifier).'|'.$request->ip()),
                Limit::perMinute(config('fortify.login_limits.ip'))->by('ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('auth-sensitive', fn (Request $request): Limit => $request->isMethod('GET') || $request->routeIs('login.store', 'logout')
            ? Limit::none()
            : Limit::perMinute(10)->by('password:'.$request->ip()));
    }
}
