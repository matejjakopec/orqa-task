<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Contracts\Audit\AuditWriterInterface;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Str;

final class AuditAuthentication
{
    public function __construct(private AuditWriterInterface $audit) {}

    public function handle(Login|Failed|Logout $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;
        $eventName = match (true) {
            $event instanceof Login => 'authentication.login_succeeded',
            $event instanceof Failed => 'authentication.login_failed',
            default => 'authentication.logout',
        };

        $this->audit->write(
            $event instanceof Failed ? null : $user,
            $eventName,
            $user === null ? 'authentication' : 'user',
            $user->public_id ?? (string) Str::uuid(),
            $user?->organisation_id,
        );
    }
}
