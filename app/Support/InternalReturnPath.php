<?php

namespace App\Support;

final class InternalReturnPath
{
    public static function sanitize(mixed $value): ?string
    {
        if (! is_string($value) || $value === '' || ! str_starts_with($value, '/')) {
            return null;
        }

        $decoded = rawurldecode($value);

        if (str_starts_with($decoded, '//')
            || str_starts_with($decoded, '/\\')
            || str_contains($decoded, '\\')
            || preg_match('/[\x00-\x1F\x7F]/', $decoded) === 1) {
            return null;
        }

        $parts = parse_url($decoded);
        if ($parts === false
            || isset($parts['scheme'])
            || isset($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])) {
            return null;
        }

        return $value;
    }
}
