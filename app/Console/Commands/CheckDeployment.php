<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Files\MalwareScannerInterface;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class CheckDeployment extends Command
{
    protected $signature = 'app:check-deployment {--production : Require production settings} {--scanner : Check ClamAV connectivity and signature freshness}';

    protected $description = 'Inspect deployment configuration and runtime prerequisites without changing application data';

    /** @var list<array{string, string, string}> */
    private array $checks = [];

    public function handle(MalwareScannerInterface $scanner): int
    {
        $this->checks = [];
        $production = (bool) $this->option('production') || app()->isProduction();
        $this->check(PHP_VERSION_ID >= 80400, 'PHP runtime', 'PHP 8.4 or newer is required by this application.');

        foreach (['pdo_mysql', 'mbstring', 'fileinfo', 'openssl', 'dom', 'xml', 'curl', 'pcntl'] as $extension) {
            $this->check(extension_loaded($extension), 'Extension: '.$extension, 'Install the extension in the application and worker runtime.');
        }

        $key = (string) config('app.key');
        $decodedKey = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : $key;
        $this->check(is_string($decodedKey) && Encrypter::supported($decodedKey, (string) config('app.cipher')), 'Application key', 'Provide a valid persistent application key; do not regenerate an existing key.');
        $this->check(! $production || app()->isProduction(), 'Production environment', 'Set APP_ENV=production on the production runtime.');
        $this->check(! $production || ! config('app.debug'), 'Debug mode', 'Disable APP_DEBUG in production.');

        $this->check(! $production || parse_url((string) config('app.url'), PHP_URL_SCHEME) === 'https', 'Canonical HTTPS URL', 'Set APP_URL to the canonical HTTPS origin.');
        $this->check(config('security.trusted_hosts') !== [], 'Trusted hosts', 'Configure APP_URL or explicit literal APP_TRUSTED_HOSTS.');
        $this->check($this->validProxies(), 'Proxy allowlist', 'Use exact IP addresses or CIDRs; wildcard trust is not accepted by this check.');
        $this->check(! $production || (config('session.secure') === true && config('session.http_only') === true && in_array(config('session.same_site'), ['lax', 'strict'], true)), 'Session cookies', 'Require secure, HTTP-only cookies with lax or strict SameSite in production.');
        $this->check(! $production || config('session.driver') === 'database', 'Session storage', 'Use database sessions so password resets can invalidate existing sessions.');
        $cacheDriver = config('cache.stores.'.config('cache.default').'.driver');
        $this->check(! $production || in_array($cacheDriver, ['database', 'redis'], true), 'Shared cache', 'Use a shared database or Redis cache for queue and scheduler locks.');

        $queueConnection = (string) config('queue.default');
        $queueDriver = config('queue.connections.'.$queueConnection.'.driver');
        $retryAfter = (int) config('queue.connections.'.$queueConnection.'.retry_after');
        $workerTimeout = (int) config('operations.worker_timeout_seconds');
        $this->check(in_array($queueDriver, ['database', 'redis'], true), 'Durable asynchronous queue', 'Configure the database or Redis queue connection.');
        $this->check($retryAfter > $workerTimeout && $workerTimeout > (int) config('files.scanner.job_timeout_seconds')
            && (int) config('files.scanner.job_timeout_seconds') > (int) config('files.scanner.timeout_seconds')
            && (int) config('files.scanner.lease_seconds') > (int) config('files.scanner.job_timeout_seconds'), 'Worker and lease timing', 'Keep scan duration < job timeout < worker timeout < queue retry_after; scan leases must outlast the job.');

        $this->check(! $production || ! in_array(config('mail.default'), ['log', 'array'], true), 'Mail transport', 'Configure a delivery transport in production; deliverability is checked separately.');
        $this->check(! $production || app()->configurationIsCached(), 'Configuration cache', 'Run php artisan optimize on the target after configuring its environment.');
        $this->check(! $production || ! is_file(public_path('hot')), 'Vite development marker', 'Remove public/hot from production releases.');
        $this->check(is_file(public_path('build/manifest.json')), 'Built frontend assets', 'Build production assets before switching the release.');

        $this->checkDatabase();
        $this->checkVault();

        $maximumUpload = (int) config('files.max_upload_bytes');
        $postLimit = $this->iniBytes((string) ini_get('post_max_size'));
        $this->check($this->iniBytes((string) ini_get('upload_max_filesize')) >= $maximumUpload
            && ($postLimit === 0 || $postLimit >= $maximumUpload + 1024 ** 2), 'PHP upload limits (CLI)', 'Allow the configured file size plus multipart overhead; verify PHP-FPM and edge limits separately.');

        if ($this->option('scanner')) {
            try {
                $scanner->version();
                $this->check(true, 'ClamAV and signatures', '');
            } catch (Throwable $exception) {
                report($exception);
                $this->check(false, 'ClamAV and signatures', 'Inspect scanner connectivity, signature updates, and server logs.');
            }
        }

        $this->table(['Check', 'Result', 'Action if needed'], $this->checks);
        $failed = count(array_filter($this->checks, fn (array $check): bool => $check[1] === 'FAIL'));
        $this->line('Read-only inspection. Persistent mounts, live proxy behavior, worker supervision, email delivery, and restores still require checks on the target host.');

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function check(bool $passed, string $label, string $action): void
    {
        $this->checks[] = [$label, $passed ? 'OK' : 'FAIL', $passed ? '' : $action];
    }

    private function checkDatabase(): void
    {
        try {
            DB::select('SELECT 1');
            $this->check(true, 'Database connectivity', '');
            $this->check(Schema::hasTable('stored_files') && Schema::hasTable('storage_accounts') && Schema::hasTable('audit_events')
                && Schema::hasColumn('users', 'setup_link_sent_at'), 'Application schema', 'Run pending migrations with the migration identity.');
        } catch (Throwable $exception) {
            report($exception);
            $this->check(false, 'Database and schema', 'Inspect database connectivity and pending migrations; details are in server logs.');
        }
    }

    private function checkVault(): void
    {
        $root = (string) config('filesystems.disks.vault.root');
        $realRoot = realpath($root);
        $realPublic = realpath(public_path());
        $outsidePublic = $realRoot !== false && $realPublic !== false && $realRoot !== $realPublic
            && ! str_starts_with($realRoot.DIRECTORY_SEPARATOR, $realPublic.DIRECTORY_SEPARATOR);
        $this->check(config('filesystems.disks.vault.driver') === 'local' && $outsidePublic
            && config('filesystems.disks.vault.serve') === false, 'Private local vault', 'Use an existing private local vault outside public/, with serving disabled.');
        $this->check(is_dir($root) && is_readable($root) && is_writable($root), 'Vault access', 'Create the persistent vault with ownership shared by the web, worker, and scheduler.');
        $permissions = @fileperms($root);
        $this->check($permissions !== false && ($permissions & 0007) === 0, 'Vault permissions', 'Remove access for other users; use application ownership and private permissions.');
        $freeBytes = @disk_free_space($root);
        $this->check($freeBytes !== false && $freeBytes >= (int) config('operations.minimum_free_disk_bytes'), 'Free disk space', 'Maintain at least the configured disk headroom; monitor inodes separately.');
    }

    private function validProxies(): bool
    {
        foreach (config('security.trusted_proxies') as $proxy) {
            $parts = explode('/', $proxy, 2);
            $address = $parts[0];

            if (filter_var($address, FILTER_VALIDATE_IP) === false) {
                return false;
            }

            if (isset($parts[1]) && (! ctype_digit($parts[1]) || (int) $parts[1] > (str_contains($address, ':') ? 128 : 32))) {
                return false;
            }
        }

        return true;
    }

    private function iniBytes(string $value): int
    {
        $value = trim($value);
        $multiplier = match (mb_strtolower(substr($value, -1))) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        };

        return (int) $value * $multiplier;
    }
}
