<?php

namespace App\Support;

class PublishedLinkToken
{
    public static function encode(int $publicationId, int $linkId): string
    {
        $value = $publicationId.':'.$linkId;
        $signature = hash_hmac('sha256', $value, (string) config('app.key'));

        return rtrim(strtr(base64_encode($value.':'.$signature), '+/', '-_'), '=');
    }

    /** @return array{publication_id: int, link_id: int}|null */
    public static function decode(string $token): ?array
    {
        $decoded = base64_decode(strtr($token, '-_', '+/'), true);

        if (! is_string($decoded)) {
            return null;
        }

        $parts = explode(':', $decoded, 3);

        if (count($parts) !== 3 || ! ctype_digit($parts[0]) || ! ctype_digit($parts[1])) {
            return null;
        }

        $value = $parts[0].':'.$parts[1];
        $expected = hash_hmac('sha256', $value, (string) config('app.key'));

        if (! hash_equals($expected, $parts[2])) {
            return null;
        }

        return ['publication_id' => (int) $parts[0], 'link_id' => (int) $parts[1]];
    }
}
