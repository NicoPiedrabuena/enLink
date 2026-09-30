<?php

namespace Tests\Feature;

use App\Models\DailyPageMetric;
use App\Models\LinkClick;
use App\Models\LinkPage;
use App\Models\PagePublication;
use App\Models\PageVisit;
use App\Models\User;
use App\Models\WebhookReceipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prunes_expired_raw_data_without_removing_daily_metrics(): void
    {
        $publication = $this->publication();

        $publication->visits()->create(['visited_at' => now()->subDays(91)]);
        $publication->visits()->create(['visited_at' => now()->subDays(89)]);
        $publication->clicks()->create(['link_key' => '1', 'link_title' => 'Sitio', 'clicked_at' => now()->subDays(91)]);
        $publication->clicks()->create(['link_key' => '1', 'link_title' => 'Sitio', 'clicked_at' => now()->subDays(89)]);
        WebhookReceipt::query()->create([
            'provider' => 'mercadopago',
            'payload_hash' => hash('sha256', 'old'),
            'payload' => [],
            'created_at' => now()->subDays(181),
            'updated_at' => now()->subDays(181),
        ]);
        WebhookReceipt::query()->create([
            'provider' => 'mercadopago',
            'payload_hash' => hash('sha256', 'recent'),
            'payload' => [],
        ]);
        DailyPageMetric::query()->create([
            'page_publication_id' => $publication->id,
            'metric_date' => now()->subDays(91)->toDateString(),
            'visits' => 4,
        ]);

        $this->artisan('enlink:prune-data --analytics-days=90 --webhook-days=180')
            ->expectsOutputToContain('Removed 1 visits, 1 clicks and 1 webhook receipts.')
            ->assertSuccessful();

        $this->assertSame(1, PageVisit::query()->count());
        $this->assertSame(1, LinkClick::query()->count());
        $this->assertSame(1, WebhookReceipt::query()->count());
        $this->assertSame(4, DailyPageMetric::query()->value('visits'));
    }

    private function publication(): PagePublication
    {
        $user = User::factory()->create();
        $page = LinkPage::query()->create([
            'user_id' => $user->id,
            'username' => 'retention-page',
            'display_name' => 'Retention Page',
        ]);

        return $page->publications()->create([
            'version' => 1,
            'username' => $page->username,
            'payload' => [],
            'published_at' => now(),
        ]);
    }
}
