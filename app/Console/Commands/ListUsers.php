<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

final class ListUsers extends Command
{
    protected $signature = 'users:list';

    protected $description = 'List account usernames, names, and assigned types';

    public function handle(): int
    {
        $users = User::query()->with('roles:id,name')->orderBy('username')->get(['id', 'username', 'first_name', 'last_name']);

        if ($users->isEmpty()) {
            $this->info('No accounts found.');

            return self::SUCCESS;
        }

        $this->table(['Username', 'Name', 'Type'], $users->map(fn (User $user): array => [
            $user->username,
            $user->display_name,
            $user->roles->map(fn (Role $role): string => $role->name->value)->sort()->implode(' + '),
        ])->all());

        return self::SUCCESS;
    }
}
