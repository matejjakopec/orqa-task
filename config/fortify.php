<?php

declare(strict_types=1);

use App\Http\Middleware\NormalizeAuthenticationInput;
use Laravel\Fortify\Features;

return [
    'guard' => 'web',
    'middleware' => ['web', NormalizeAuthenticationInput::class, 'throttle:auth-sensitive'],
    'auth_middleware' => 'auth',
    'passwords' => 'users',
    'username' => 'username',
    'email' => 'email',
    'lowercase_usernames' => false,
    'views' => true,
    'home' => '/',
    'prefix' => '',
    'domain' => null,
    'limiters' => ['login' => 'login'],
    'login_limits' => [
        'username' => (int) env('AUTH_LOGIN_ATTEMPTS', 5),
        'ip' => (int) env('AUTH_LOGIN_IP_ATTEMPTS', 30),
    ],
    'redirects' => ['logout' => '/login'],
    'features' => [
        Features::resetPasswords(),
    ],
];
