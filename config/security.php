<?php

declare(strict_types=1);

$hosts = array_values(array_filter(array_map(trim(...), explode(',', (string) env('APP_TRUSTED_HOSTS', '')))));
$applicationHost = parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST);

if ($hosts === [] && is_string($applicationHost) && $applicationHost !== '') {
    $hosts = [$applicationHost];
}

return [
    'trusted_hosts' => array_map(fn (string $host): string => '^'.preg_quote($host).'$', $hosts),
    'trusted_proxies' => array_values(array_filter(array_map(trim(...), explode(',', (string) env('APP_TRUSTED_PROXIES', ''))))),
    'hsts_max_age' => max(0, (int) env('APP_HSTS_MAX_AGE', 0)),
];
