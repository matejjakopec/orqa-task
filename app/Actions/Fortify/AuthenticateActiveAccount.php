<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use Closure;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class AuthenticateActiveAccount
{
    public function __construct(private StatefulGuard $guard) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $credentials = $request->only('username', 'password');
        $credentials[] = fn (Builder $query): Builder => $query->whereNotNull('activated_at')->whereNull('disabled_at');

        if (! $this->guard->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['username' => __('auth.failed')]);
        }

        return $next($request);
    }
}
