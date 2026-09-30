<?php

namespace App\Console\Commands;

use App\Actions\Payments\PaymentProcessingUnavailableException;
use App\Actions\Payments\ProcessMercadoPagoWebhookAction;
use App\Models\PaymentOrder;
use Illuminate\Console\Command;
use Throwable;

class ReconcileMercadoPagoPaymentsCommand extends Command
{
    protected $signature = 'enlink:reconcile-payments {--hours= : Maximum age of pending orders to reconcile}';

    protected $description = 'Reconciles recently pending Mercado Pago orders to prevent missed credits.';

    public function handle(ProcessMercadoPagoWebhookAction $processor): int
    {
        $hours = $this->hours();
        $orders = PaymentOrder::query()
            ->where('provider', 'mercadopago')
            ->whereIn('status', ['awaiting_checkout', 'pending'])
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderBy('id')
            ->get();

        $processed = 0;
        $unavailable = 0;

        foreach ($orders as $order) {
            try {
                if ($processor->reconcile($order) === 'processed') {
                    $processed++;
                }
            } catch (PaymentProcessingUnavailableException $exception) {
                report($exception);
                $unavailable++;
            } catch (Throwable $exception) {
                report($exception);
                $unavailable++;
            }
        }

        $this->info("Reconciled {$orders->count()} pending orders; credited {$processed}.");

        if ($unavailable > 0) {
            $this->error("{$unavailable} orders could not be reconciled and will be retried by the next schedule.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function hours(): int
    {
        $value = $this->option('hours') ?? config('enlink.payment_reconciliation_hours');

        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1 || (int) $value > 720) {
            throw new \InvalidArgumentException('The --hours value must be an integer between 1 and 720.');
        }

        return (int) $value;
    }
}
