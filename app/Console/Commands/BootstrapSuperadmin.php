<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Fortify\PasswordValidationRules;
use App\Contracts\Audit\AuditWriterInterface;
use App\Contracts\Users\UserRepositoryInterface;
use App\Data\Users\CreateUserData;
use App\Enums\RoleName;
use App\Support\AccountIdentifier;
use App\Support\AccountValidationRules;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

final class BootstrapSuperadmin extends Command
{
    use AccountValidationRules, PasswordValidationRules;

    protected $signature = 'users:bootstrap-superadmin';

    protected $description = 'Create an active global superadmin using interactive, hidden password prompts';

    public function handle(UserRepositoryInterface $users, AuditWriterInterface $audit): int
    {
        if (! $this->input->isInteractive()) {
            $this->error(__('accounts.interactive_required'));

            return self::FAILURE;
        }

        $data = [
            'username' => AccountIdentifier::normalize((string) $this->ask('Username')),
            'email' => AccountIdentifier::normalize((string) $this->ask('Email address')),
            'first_name' => trim((string) $this->ask('First name')),
            'last_name' => trim((string) $this->ask('Last name')),
            'password' => $this->secret('Password'),
            'password_confirmation' => $this->secret('Confirm password'),
        ];
        $validator = Validator::make($data, $this->identityRules() + $this->profileRules() + ['password' => $this->passwordRules()]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        try {
            $passwordHash = Hash::make($data['password']);
            DB::transaction(function () use ($users, $audit, $data, $passwordHash): void {
                $user = $users->create(new CreateUserData(
                    username: $data['username'], email: $data['email'],
                    firstName: $data['first_name'], lastName: $data['last_name'],
                    organisationId: null, roles: [RoleName::Superadmin],
                ), $passwordHash, 0);
                $user->forceFill(['activated_at' => now()])->save();
                $audit->write(null, 'superadmin.created', 'user', $user->public_id, null, ['roles' => ['superadmin']]);
            });
        } catch (UniqueConstraintViolationException) {
            $this->error(__('accounts.duplicate_account'));

            return self::FAILURE;
        }

        $this->info(__('accounts.superadmin_created'));

        return self::SUCCESS;
    }
}
