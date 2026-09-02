<?php

return [
    'password' => [
        'min_length' => (int) env('AUTH_PASSWORD_MIN_LENGTH', 12),
        'max_length' => (int) env('AUTH_PASSWORD_MAX_LENGTH', 255),
    ],

    'lockout' => [
        'threshold' => (int) env('AUTH_LOCKOUT_THRESHOLD', 5),
        'base_seconds' => (int) env('AUTH_LOCKOUT_BASE_SECONDS', 60),
        'max_seconds' => (int) env('AUTH_LOCKOUT_MAX_SECONDS', 900),
    ],
];
