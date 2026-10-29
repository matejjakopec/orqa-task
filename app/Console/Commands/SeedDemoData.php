<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;
use LogicException;

final class SeedDemoData extends Command
{
    protected $signature = 'demo:seed
        {--users=10 : Ordinary user accounts}
        {--admins=2 : Admin-only accounts}
        {--admin-users=1 : Accounts with both admin and user access}
        {--superadmins=1 : Global superadmin accounts}
        {--organisations=3 : Organisations to create}
        {--files=7 : Random files per account with personal storage, up to 20}';

    protected $description = 'Create random local demo accounts and organisations with a shared demo password';

    public function handle(DemoSeeder $seeder): int
    {
        $parameters = [];

        foreach (['users' => 'users', 'admins' => 'admins', 'admin-users' => 'adminUsers', 'superadmins' => 'superadmins', 'organisations' => 'organisations', 'files' => 'filesPerUser'] as $option => $parameter) {
            $count = filter_var($this->option($option), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000]]);

            if ($count === false) {
                $this->error('--'.$option.' must be an integer between 0 and 1000.');

                return self::FAILURE;
            }

            $parameters[$parameter] = $count;
        }

        try {
            $seeder->setContainer($this->laravel)->setCommand($this);
            $seeder->__invoke($parameters);
        } catch (LogicException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
