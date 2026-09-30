<?php

namespace App\Actions\Payments;

use App\Actions\Credits\CreateCreditTransactionAction;
use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\WebhookReceipt;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class ProcessMercadoPagoWebhookAction
{
    public function __construct(private readonly CreateCreditTransactionAction $createCreditTransaction) {}

    /**
     * @param  array<string, mixed>  $notification
     */
    public function execute(array $notification): WebhookReceipt
    {
        $receipt = WebhookReceipt::query()->create([
            'provider' => 'mercadopago',
            'provider_event_id' => isset($notification['id']) ? (string) $notification['id'] : null,
            'payload_hash' => hash('sha256', json_encode($notification, JSON_THROW_ON_ERROR)),
            // Payment data is retrieved from Mercado Pago with our token. Keep
            // only the delivery identifiers needed for support and idempotency.
            'payload' => $this->receiptPayload($notification),
        ]);

        $paymentId = data_get($notification, 'data.id');

        if (! is_scalar($paymentId)) {
            return $this->finish($receipt, 'ignored', 'La notificación no contiene un pago.');
        }

        try {
            $status = $this->processPaymentId((string) $paymentId);
        } catch (PaymentProcessingUnavailableException $exception) {
            $this->finish($receipt, 'failed', 'No se pudo verificar el pago con Mercado Pago.');

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            $this->finish($receipt, 'failed', 'No se pudo acreditar el pago.');

            throw new PaymentProcessingUnavailableException('No se pudo acreditar el pago.', previous: $exception);
        }

        return $this->finish($receipt, $status, $status === 'rejected' ? 'El pago no coincide con la orden.' : null);
    }

    public function reconcile(PaymentOrder $order): string
    {
        try {
            $payload = Http::acceptJson()
                ->withToken((string) config('mercadopago.access_token'))
                ->retry(3, 200)
                ->timeout(10)
                ->get(rtrim((string) config('mercadopago.base_url'), '/').'/v1/payments/search', [
                    'external_reference' => $order->external_reference,
                    'sort' => 'date_created',
                    'criteria' => 'desc',
                    'limit' => 20,
                ])
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            report($exception);

            throw new PaymentProcessingUnavailableException('No se pudieron conciliar los pagos.', previous: $exception);
        }

        foreach ($payload['results'] ?? [] as $paymentData) {
            if (is_array($paymentData) && ($paymentData['status'] ?? null) === 'approved' && is_scalar($paymentData['id'] ?? null)) {
                return $this->processPayment((string) $paymentData['id'], $paymentData);
            }
        }

        return 'ignored';
    }

    private function processPaymentId(string $paymentId): string
    {
        try {
            $paymentData = Http::acceptJson()
                ->withToken((string) config('mercadopago.access_token'))
                ->retry(3, 200)
                ->timeout(10)
                ->get(rtrim((string) config('mercadopago.base_url'), '/').'/v1/payments/'.rawurlencode($paymentId))
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            report($exception);

            throw new PaymentProcessingUnavailableException('No se pudo verificar el pago con Mercado Pago.', previous: $exception);
        }

        return $this->processPayment($paymentId, $paymentData);
    }

    /** @param array<string, mixed> $paymentData */
    private function processPayment(string $paymentId, array $paymentData): string
    {
        if (($paymentData['status'] ?? null) !== 'approved') {
            return 'ignored';
        }

        $externalReference = $paymentData['external_reference'] ?? null;

        if (! is_string($externalReference) || $externalReference === '') {
            return 'rejected';
        }

        return DB::transaction(function () use ($externalReference, $paymentData, $paymentId): string {
            $order = PaymentOrder::query()
                ->where('external_reference', $externalReference)
                ->lockForUpdate()
                ->first();

            if (! $order || ! $this->matchesOrder($order, $paymentData)) {
                return 'rejected';
            }

            $payment = Payment::query()
                ->where('provider', 'mercadopago')
                ->where('provider_payment_id', $paymentId)
                ->lockForUpdate()
                ->first();

            if ($payment) {
                return 'duplicate';
            }

            $payment = $order->payments()->create([
                'provider' => 'mercadopago',
                'provider_payment_id' => $paymentId,
                'status' => 'approved',
                'amount' => $order->amount,
                'currency' => $order->currency,
                'metadata' => [
                    'payment_type_id' => $paymentData['payment_type_id'] ?? null,
                    'date_approved' => $paymentData['date_approved'] ?? null,
                ],
                'paid_at' => now(),
            ]);

            $this->createCreditTransaction->execute(
                $order->user,
                'purchase',
                $order->credits,
                sprintf('Compra de %d créditos.', $order->credits),
                'payment:mercadopago:'.$payment->provider_payment_id,
                ['payment_order_id' => $order->id, 'payment_id' => $payment->id],
            );

            $order->update(['status' => 'approved']);

            return 'processed';
        });
    }

    /**
     * @param  array<string, mixed>  $paymentData
     */
    private function matchesOrder(PaymentOrder $order, array $paymentData): bool
    {
        $amount = $paymentData['transaction_amount'] ?? null;

        return is_numeric($amount)
            && number_format((float) $amount, 2, '.', '') === $order->amount
            && ($paymentData['currency_id'] ?? null) === $order->currency;
    }

    private function finish(WebhookReceipt $receipt, string $status, ?string $error = null): WebhookReceipt
    {
        $receipt->update(['status' => $status, 'error' => $error, 'processed_at' => now()]);

        return $receipt;
    }

    /** @param array<string, mixed> $notification @return array<string, mixed> */
    private function receiptPayload(array $notification): array
    {
        return array_filter([
            'id' => is_scalar($notification['id'] ?? null) ? (string) $notification['id'] : null,
            'type' => is_scalar($notification['type'] ?? null) ? (string) $notification['type'] : null,
            'data_id' => is_scalar(data_get($notification, 'data.id')) ? (string) data_get($notification, 'data.id') : null,
        ], static fn (?string $value): bool => $value !== null);
    }
}
