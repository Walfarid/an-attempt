<?php

namespace App\Support;

class Analytics
{
    /**
     * Anonymize an IP address before it is stored with analytics rows.
     *
     * The raw address is never persisted — only an HMAC-SHA256 digest
     * keyed with the app key, which is enough to count unique visitors
     * while keeping the original address unrecoverable.
     */
    public static function anonymizeIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }
}
