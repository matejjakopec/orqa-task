<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('files:recover --limit=1000 --resume')
    ->everyMinute()->withoutOverlapping(30)->onOneServer()
    ->runInBackground()->appendOutputTo(storage_path('logs/file-recovery.log'));

Schedule::command('users:recover-setup-links --limit=1000 --resume')
    ->everyFiveMinutes()->withoutOverlapping(30)->onOneServer()
    ->runInBackground()->appendOutputTo(storage_path('logs/setup-link-recovery.log'));

Schedule::command('storage:reconcile --limit=100 --resume')
    ->hourly()->withoutOverlapping(120)->onOneServer()
    ->runInBackground()->appendOutputTo(storage_path('logs/storage-reconciliation.log'));

Schedule::command('files:status --json')
    ->everyFiveMinutes()->withoutOverlapping(10)->onOneServer()
    ->appendOutputTo(storage_path('logs/file-status.log'));

$queueConnection = config('queue.default');

if (in_array(config('queue.connections.'.$queueConnection.'.driver'), ['database', 'redis'], true)) {
    $queueName = config('queue.connections.'.$queueConnection.'.queue', 'default');
    Schedule::command('queue:monitor', [$queueConnection.':'.$queueName, '--max' => config('operations.queue_warning_size'), '--json'])
        ->everyMinute()->withoutOverlapping(10)->onOneServer()
        ->appendOutputTo(storage_path('logs/queue-monitor.log'));
}
