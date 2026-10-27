<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Seeder;

final class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RoleName::cases() as $name) {
            Role::query()->firstOrCreate(['name' => $name->value]);
        }
    }
}
