<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Contracts\Files\FileServiceInterface;
use App\Data\Files\IncomingFile;
use App\Enums\RoleName;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

final class DemoSeeder extends Seeder
{
    private const PASSWORD = 'local-demo-password';

    public function run(
        FileServiceInterface $files,
        int $users = 10,
        int $admins = 2,
        int $adminUsers = 1,
        int $superadmins = 1,
        int $organisations = 3,
        int $filesPerUser = 7,
    ): void {
        if (! app()->environment('local')) {
            throw new LogicException('Demo data is restricted to the local environment.');
        }

        $counts = [$users, $admins, $adminUsers, $superadmins, $organisations];

        if (min($counts) < 0 || max($counts) > 1000 || $users + $admins + $adminUsers + $superadmins > 1000
            || $filesPerUser < 0 || $filesPerUser > 20) {
            throw new InvalidArgumentException('Use non-negative counts, at most 1000 accounts/organisations, and 0–20 files per storage account.');
        }

        if ($organisations === 0 && $users + $admins + $adminUsers > 0) {
            throw new InvalidArgumentException('User and admin accounts require at least one organisation.');
        }

        $this->callSilent(RoleSeeder::class);
        $passwordHash = Hash::make(self::PASSWORD);

        /** @var list<User> $accounts */
        $accounts = DB::transaction(function () use ($users, $admins, $adminUsers, $superadmins, $organisations, $passwordHash): array {
            $organisationIds = Organisation::factory()->count($organisations)->create()->modelKeys();
            $accounts = [];
            $organisationIndex = 0;

            foreach ([
                ['count' => $users, 'roles' => [RoleName::User]],
                ['count' => $admins, 'roles' => [RoleName::Admin]],
                ['count' => $adminUsers, 'roles' => [RoleName::Admin, RoleName::User]],
                ['count' => $superadmins, 'roles' => [RoleName::Superadmin]],
            ] as $group) {
                for ($index = 0; $index < $group['count']; $index++) {
                    $organisationId = in_array(RoleName::Superadmin, $group['roles'], true)
                        ? null
                        : $organisationIds[$organisationIndex++ % count($organisationIds)];
                    $accounts[] = User::factory()->withRoles(...$group['roles'])->create([
                        'organisation_id' => $organisationId,
                        'password' => $passwordHash,
                    ]);
                }
            }

            return $accounts;
        }, 3);

        if (isset($this->command)) {
            $this->command->info('Created '.count($accounts).' accounts and '.$organisations.' organisations.');
        }

        if ($filesPerUser === 0) {
            return;
        }

        foreach ($accounts as $account) {
            if (! $account->hasRole(RoleName::User)) {
                continue;
            }

            for ($index = 0; $index < $filesPerUser; $index++) {
                $this->seedFile($files, $account, fake()->unique()->slug(3).'.txt', fake()->paragraph().PHP_EOL);
            }
        }
    }

    private function seedFile(FileServiceInterface $files, User $owner, string $name, string $contents): void
    {
        $temporary = tmpfile();

        if ($temporary === false) {
            throw new RuntimeException('The demo upload temporary file cannot be created.');
        }

        try {
            if (fwrite($temporary, $contents) !== strlen($contents)) {
                throw new RuntimeException('The demo upload temporary file could not be written.');
            }

            $upload = new UploadedFile(stream_get_meta_data($temporary)['uri'], $name, test: true);
            $files->upload($owner, IncomingFile::fromUpload($upload));
        } finally {
            fclose($temporary);
        }
    }
}
