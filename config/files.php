<?php

declare(strict_types=1);

return [
    'default_quota_bytes' => (int) env('FILES_DEFAULT_QUOTA_BYTES', 3 * 1024 ** 3),
    'max_upload_bytes' => 1024 ** 3,
    'orphan_grace_minutes' => 60,
    'scanner' => [
        'host' => env('CLAMAV_HOST', 'clamav'),
        'port' => (int) env('CLAMAV_PORT', 3310),
        'connect_timeout_seconds' => 3,
        'timeout_seconds' => 360,
        'job_timeout_seconds' => 420,
        'lease_seconds' => 480,
        'max_attempts' => 5,
        'max_signature_age_hours' => 72,
    ],
];
