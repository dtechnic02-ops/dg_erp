<?php

return [
    'network_submission_enabled' => false,
    'timeout_seconds' => 15,
    'tls_verify' => true,
    'content_type' => 'application/json',
    'realtime_window_seconds' => 300,
    'max_attempts' => 5,
    'retry_delays_seconds' => [60, 300, 900, 3600],
    'max_response_excerpt_bytes' => 4096,
    'endpoints' => [
        'bill' => 'https://cbapi.ird.gov.np/api/bill',
        'bill_return' => 'https://cbapi.ird.gov.np/api/billreturn',
    ],
];
