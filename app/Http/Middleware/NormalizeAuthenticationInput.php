<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\AccountIdentifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class NormalizeAuthenticationInput
{
    public function handle(Request $request, Closure $next): Response
    {
        foreach (['username', 'email'] as $field) {
            if (is_string($request->input($field))) {
                $request->merge([$field => AccountIdentifier::normalize($request->input($field))]);
            }
        }

        return $next($request);
    }
}
