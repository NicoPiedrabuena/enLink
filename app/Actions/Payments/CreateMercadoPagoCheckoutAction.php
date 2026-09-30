<?php

namespace App\Actions\Payments;

use App\Models\CreditPackage;
use App\Models\PaymentOrder;
use App\Models\User;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CreateMercadoPagoCheckoutAction
{
    public function execute(User $user, CreditPackage $package): PaymentOrder
    {
        if (! $package->is_active) {
            throw new CheckoutUnavailableException('Este paquete no está disponible.');
        }

        $accessToken = config('mercadopago.access_token');

        if (! is_string($accessToken) || $accessToken === '') {
            throw new CheckoutUnavailableException('Los pagos todavía no están configurados.');
        }

        $order = DB::transaction(function () use ($package, $user): PaymentOrder {
            return $user->paymentOrders()->create([
                'credit_package_id' => $package->id,
                'package_name' => $package->name,
                'credits' => $package->credits,
                'amount' => $package->price,
                'currency' => $package->currency,
                'external_reference' => Str::uuid()->toString(),
            ]);
        });

        try {
            $returnUrl = route('credits.index');
            $webhookUrl = route('webhooks.mercadopago');
            $preference = [
                'items' => [[
                    'title' => sprintf('%d créditos Enlink', $order->credits),
                    'quantity' => 1,
                    'currency_id' => $order->currency,
                    'unit_price' => (float) $order->amount,
                ]],
                'external_reference' => $order->external_reference,
                'metadata' => ['payment_order_id' => $order->id],
            ];

            // Mercado Pago requires publicly reachable callback URLs. Localhost can
            // still create and open a sandbox checkout, but cannot receive callbacks.
            if (str_starts_with($returnUrl, 'https://') && str_starts_with($webhookUrl, 'https://')) {
                $preference['notification_url'] = $webhookUrl;
                $preference['back_urls'] = [
                    'success' => $returnUrl,
                    'failure' => $returnUrl,
                    'pending' => $returnUrl,
                ];
                $preference['auto_return'] = 'approved';
            }

            $response = Http::acceptJson()
                ->withToken($accessToken)
                ->timeout(10)
                ->post(rtrim((string) config('mercadopago.base_url'), '/').'/checkout/preferences', $preference);

            $response->throw();
            $payload = $response->json();
            $checkoutUrl = config('mercadopago.sandbox')
                ? $payload['sandbox_init_point'] ?? null
                : $payload['init_point'] ?? null;

            if (! is_string($checkoutUrl) || $checkoutUrl === '') {
                throw new CheckoutUnavailableException('Mercado Pago no devolvió una URL de pago.');
            }

            $order->update([
                'provider_preference_id' => $payload['id'] ?? null,
                'checkout_url' => $checkoutUrl,
                'status' => 'pending',
            ]);
        } catch (RequestException|CheckoutUnavailableException $exception) {
            $order->update(['status' => 'preference_failed']);

            if ($exception instanceof CheckoutUnavailableException) {
                throw $exception;
            }

            report($exception);

            throw new CheckoutUnavailableException('No se pudo iniciar el pago. Intentá nuevamente.');
        }

        return $order->fresh();
    }
}
