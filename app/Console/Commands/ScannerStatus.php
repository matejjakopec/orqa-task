<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Files\MalwareScannerInterface;
use App\Exceptions\ScannerUnavailable;
use Illuminate\Console\Command;

final class ScannerStatus extends Command
{
    protected $signature = 'files:scanner-status';

    protected $description = 'Check ClamAV connectivity and signature freshness';

    public function handle(MalwareScannerInterface $scanner): int
    {
        try {
            $version = $scanner->version();
        } catch (ScannerUnavailable $exception) {
            report($exception);
            $this->error(__('files.scanner_unavailable'));

            return self::FAILURE;
        }

        $this->info(__('files.scanner_ready', $version));

        return self::SUCCESS;
    }
}
