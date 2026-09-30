<?php

namespace Tests\Feature;

use App\Actions\Payments\CreateMercadoPagoCheckoutAction;
use App\Models\CreditBalance;
use App\Models\CreditPackage;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\BusinessModelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class MonetizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_starter_package_costs_five_thousand_pesos_and_grants_three_credits(): void
    {
        $this->seed(BusinessModelSeeder::class);

        $this->assertDatabaseHas('credit_packages', [
            'name' => 'Pack inicial',
            'credits' => 3,
            'price' => 5000,
            'currency' => 'ARS',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('credit_packages', [
            'name' => 'Pack medio',
            'credits' => 10,
            'price' => 10000,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('credit_packages', [
            'name' => 'Pack pro',
            'credits' => 50,
            'price' => 30000,
            'is_active' => true,
        ]);
    }

    public function test_credits_screen_lists_active_packages_only(): void
    {
        CreditPackage::query()->create([
            'name' => '10 créditos',
            'credits' => 10,
            'price' => 1000,
            'currency' => 'ARS',
            'is_active' => true,
        ]);
        CreditPackage::query()->create([
            'name' => 'Oculto',
            'credits' => 50,
            'price' => 4000,
            'currency' => 'ARS',
        ]);

        $packages = CreditPackage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name')
            ->all();

        $this->assertSame(['10 créditos'], $packages);
    }

    public function test_checkout_captures_the_package_details_before_redirecting_to_mercado_pago(): void
    {
        config()->set('mercadopago.access_token', 'TEST-token');
        config()->set('mercadopago.sandbox', true);

        Http::fake([
            'https://api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'preference-123',
                'sandbox_init_point' => 'https://sandbox.mercadopago.test/checkout',
            ]),
        ]);

        $user = User::factory()->create();
        $package = CreditPackage::query()->create([
            'name' => '50 créditos',
            'credits' => 50,
            'price' => 4000,
            'currency' => 'ARS',
            'is_active' => true,
        ]);

        $order = app(CreateMercadoPagoCheckoutAction::class)->execute($user, $package);

        $this->assertSame('pending', $order->status);
        $this->assertSame('50 créditos', $order->package_name);
        $this->assertSame(50, $order->credits);
        $this->assertSame('4000.00', $order->amount);
        $this->assertSame('preference-123', $order->provider_preference_id);
        $this->assertSame('https://sandbox.mercadopago.test/checkout', $order->checkout_url);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.mercadopago.com/checkout/preferences'
            && $request['external_reference'] === $order->external_reference
            && $request['items'][0]['unit_price'] === 4000.0
            && ! isset($request['auto_return'])
            && ! isset($request['notification_url']));
    }

    public function test_an_approved_payment_is_credited_once_even_when_the_webhook_is_retried(): void
    {
        config()->set('mercadopago.access_token', 'TEST-token');
        config()->set('mercadopago.webhook_secret', 'webhook-secret');

        $user = User::factory()->create();
        $order = $user->paymentOrders()->create([
            'package_name' => '10 créditos',
            'credits' => 10,
            'amount' => 1000,
            'currency' => 'ARS',
            'external_reference' => Str::uuid()->toString(),
        ]);

        Http::fake([
            'https://api.mercadopago.com/v1/payments/payment-123' => Http::response([
                'id' => 'payment-123',
                'status' => 'approved',
                'external_reference' => $order->external_reference,
                'transaction_amount' => 1000,
                'currency_id' => 'ARS',
                'payment_type_id' => 'account_money',
            ]),
        ]);

        $this->sendWebhook('event-1', 'payment-123');
        $this->sendWebhook('event-2', 'payment-123');

        $this->assertSame(1, Payment::query()->count());
        $this->assertSame(1, $user->fresh()->creditTransactions()->where('type', 'purchase')->count());
        $this->assertSame(10, CreditBalance::query()->where('user_id', $user->id)->value('balance'));
        $this->assertSame('approved', $order->fresh()->status);
    }

    public function test_webhook_with_an_invalid_signature_is_rejected(): void
    {
        config()->set('mercadopago.webhook_secret', 'webhook-secret');

        $this->postJson('/webhooks/mercado-pago?data.id=payment-123', ['data' => ['id' => 'payment-123']], [
            'x-signature' => 'ts=1700000000,v1=invalid',
            'x-request-id' => 'request-123',
        ])->assertUnauthorized();
    }

    public function test_webhook_returns_a_retryable_error_when_mercado_pago_cannot_be_reached(): void
    {
        config()->set('mercadopago.access_token', 'TEST-token');
        config()->set('mercadopago.webhook_secret', 'webhook-secret');

        Http::fake([
            'https://api.mercadopago.com/v1/payments/payment-123' => Http::response([], 503),
        ]);

        $timestamp = '1700000000';
        $paymentId = 'payment-123';
        $signature = hash_hmac('sha256', "id:{$paymentId};request-id:request-1;ts:{$timestamp};", 'webhook-secret');

        $this->postJson('/webhooks/mercado-pago?data.id='.$paymentId, [
            'id' => 'event-1',
            'type' => 'payment',
            'data' => ['id' => $paymentId],
        ], [
            'x-signature' => 'ts='.$timestamp.',v1='.$signature,
            'x-request-id' => 'request-1',
        ])->assertServiceUnavailable();

        $this->assertDatabaseHas('webhook_receipts', ['provider_event_id' => 'event-1', 'status' => 'failed']);
    }

    public function test_scheduled_reconciliation_credits_an_approved_pending_order(): void
    {
        config()->set('mercadopago.access_token', 'TEST-token');

        $user = User::factory()->create();
        $order = $user->paymentOrders()->create([
            'package_name' => '10 créditos',
            'credits' => 10,
            'amount' => 1000,
            'currency' => 'ARS',
            'external_reference' => Str::uuid()->toString(),
            'status' => 'pending',
        ]);

        Http::fake([
            'https://api.mercadopago.com/v1/payments/search*' => Http::response([
                'results' => [[
                    'id' => 'payment-reconciled',
                    'status' => 'approved',
                    'external_reference' => $order->external_reference,
                    'transaction_amount' => 1000,
                    'currency_id' => 'ARS',
                ]],
            ]),
        ]);

        $this->artisan('enlink:reconcile-payments')
            ->expectsOutputToContain('Reconciled 1 pending orders; credited 1.')
            ->assertSuccessful();

        $this->assertSame('approved', $order->fresh()->status);
        $this->assertSame(10, CreditBalance::query()->where('user_id', $user->id)->value('balance'));
        $this->assertDatabaseHas('payments', ['provider_payment_id' => 'payment-reconciled']);
    }

    private function sendWebhook(string $eventId, string $paymentId): void
    {
        $timestamp = '1700000000';
        $requestId = 'request-'.$eventId;
        $signature = hash_hmac('sha256', sprintf('id:%s;request-id:%s;ts:%s;', $paymentId, $requestId, $timestamp), 'webhook-secret');

        $this->postJson('/webhooks/mercado-pago?data.id='.$paymentId, [
            'id' => $eventId,
            'type' => 'payment',
            'data' => ['id' => $paymentId],
        ], [
            'x-signature' => 'ts='.$timestamp.',v1='.$signature,
            'x-request-id' => $requestId,
        ])->assertOk();
    }
}
