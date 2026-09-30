<?php

namespace App\Console\Commands;

use App\Models\LinkClick;
use App\Models\PageVisit;
use App\Models\WebhookReceipt;
use Illuminate\Console\Command;

class PruneDataCommand extends Command
{
    protected $signature = 'enlink:prune-data
        {--analytics-days= : Days to retain raw visits and clicks}
        {--webhook-days= : Days to retain webhook receipts}';

    protected $description = 'Removes expired raw analytics and webhook receipts while keeping aggregated metrics.';

    public function handle(): int
    {
        $analyticsDays = $this->days('analytics-days', 'enlink.analytics_retention_days');
        $webhookDays = $this->days('webhook-days', 'enlink.webhook_retention_days');

        $visits = PageVisit::query()
            ->where('visited_at', '<', now()->subDays($analyticsDays))
            ->delete();
        $clicks = LinkClick::query()
            ->where('clicked_at', '<', now()->subDays($analyticsDays))
            ->delete();
        $webhooks = WebhookReceipt::query()
            ->where('created_at', '<', now()->subDays($webhookDays))
            ->delete();

        $this->info("Removed {$visits} visits, {$clicks} clicks and {$webhooks} webhook receipts.");

        return self::SUCCESS;
    }

    private function days(string $option, string $config): int
    {
        $value = $this->option($option) ?? config($config);

        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 30) {
            throw new \InvalidArgumentException("The --{$option} value must be an integer of at least 30 days.");
        }

        return (int) $value;
    }
}
