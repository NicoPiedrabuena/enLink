<?php

namespace App\Support;

class TrafficContext
{
    public static function isBot(?string $userAgent): bool
    {
        return is_string($userAgent)
            && preg_match('/bot|crawler|spider|slurp|bingpreview|facebookexternalhit/i', $userAgent) === 1;
    }

    public static function referrerDomain(?string $referrer): ?string
    {
        if (! is_string($referrer) || $referrer === '') {
            return null;
        }

        $host = parse_url($referrer, PHP_URL_HOST);

        return is_string($host) ? mb_strtolower(mb_substr($host, 0, 255)) : null;
    }

    public static function userAgentFamily(?string $userAgent): ?string
    {
        if (! is_string($userAgent) || $userAgent === '') {
            return null;
        }

        return match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Other',
        };
    }
}
