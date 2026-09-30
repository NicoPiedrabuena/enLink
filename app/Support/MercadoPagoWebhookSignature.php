<?php

namespace App\Support;

use Illuminate\Http\Request;

class MercadoPagoWebhookSignature
{
    public static function isValid(Request $request): bool
    {
        $secret = config('mercadopago.webhook_secret');
        $signature = $request->header('x-signature');
        $requestId = $request->header('x-request-id');
        $dataId = $request->query('data.id') ?? data_get($request->all(), 'data.id') ?? $request->query('data_id');

        if (! is_string($secret) || $secret === '' || ! is_string($signature) || ! is_string($requestId) || ! is_scalar($dataId)) {
            return false;
        }

        $parts = [];

        foreach (explode(',', $signature) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);

            if (is_string($key) && is_string($value)) {
                $parts[$key] = $value;
            }
        }

        if (! isset($parts['ts'], $parts['v1'])) {
            return false;
        }

        $manifest = sprintf('id:%s;request-id:%s;ts:%s;', $dataId, $requestId, $parts['ts']);
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $parts['v1']);
    }
}
