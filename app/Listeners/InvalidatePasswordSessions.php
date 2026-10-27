<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;

final class InvalidatePasswordSessions
{
    public function handle(PasswordReset $event): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))
            ->table(config('session.table'))
            ->where('user_id', $event->user->getAuthIdentifier())
            ->delete();
    }
}
