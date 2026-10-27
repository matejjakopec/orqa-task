<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

final class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /** @param array<string, mixed> $input */
    public function reset(User $user, array $input): void
    {
        $validated = Validator::make($input, ['password' => $this->passwordRules()])->validate();
        $passwordHash = Hash::make($validated['password']);

        DB::transaction(function () use ($user, $passwordHash): void {
            $account = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($account->disabled_at !== null) {
                throw ValidationException::withMessages(['email' => __('passwords.token')]);
            }

            $account->forceFill([
                'password' => $passwordHash,
                'activated_at' => $account->activated_at ?? now(),
            ])->save();
        });

        $user->refresh();
    }
}
