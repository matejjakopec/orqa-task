<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (app()->isProduction()) {
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'", "script-src 'self'", "style-src 'self'",
                "img-src 'self' data:", "font-src 'self'", "connect-src 'self'",
                "object-src 'none'", "base-uri 'none'", "form-action 'self'", "frame-ancestors 'none'",
            ]));

            $hstsMaxAge = (int) config('security.hsts_max_age');

            if ($request->isSecure() && $hstsMaxAge > 0) {
                $response->headers->set('Strict-Transport-Security', 'max-age='.$hstsMaxAge);
            }
        }

        return $response;
    }
}
