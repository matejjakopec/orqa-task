<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\Audit\AuditWriterInterface;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use RuntimeException;

final class SendAccountSetupLink implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 60;

    public int $uniqueFor = 3600;

    public function __construct(public int $userId) {}

    public function uniqueId(): string
    {
        return (string) $this->userId;
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(AuditWriterInterface $audit): void
    {
        $user = User::query()->find($this->userId);

        if ($user === null || $user->activated_at !== null || $user->disabled_at !== null || $user->setup_link_sent_at !== null) {
            return;
        }

        $broker = config('fortify.passwords', 'users');
        $status = Password::broker($broker)->sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_THROTTLED) {
            $this->release((int) config('auth.passwords.'.$broker.'.throttle', 60) + 1);

            return;
        }

        if ($status !== Password::RESET_LINK_SENT) {
            throw new RuntimeException('The account setup link could not be sent.');
        }

        DB::transaction(function () use ($audit): void {
            $current = User::query()->lockForUpdate()->find($this->userId);

            if ($current === null || $current->setup_link_sent_at !== null) {
                return;
            }

            $current->setup_link_sent_at = now();
            $current->save();
            $audit->write(null, 'account.setup_link_sent', 'user', $current->public_id, $current->organisation_id);
        }, 3);
    }
}
