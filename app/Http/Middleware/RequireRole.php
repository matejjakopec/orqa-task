<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\RoleName;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $actor = $request->user();

        abort_unless($actor instanceof User && $actor->exists && $actor->isActive(), 403);

        $actor->loadMissing('roles');

        foreach ($roles as $role) {
            $name = RoleName::tryFrom($role);

            if ($name !== null && $actor->hasRole($name)) {
                return $next($request);
            }
        }

        abort(403);
    }
}
