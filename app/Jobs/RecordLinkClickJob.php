<?php

namespace App\Jobs;

use App\Models\DailyLinkMetric;
use App\Models\PagePublication;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class RecordLinkClickJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $publicationId,
        public readonly string $linkKey,
        public readonly string $linkTitle,
        public readonly ?string $referrerDomain,
        public readonly ?string $userAgentFamily,
    ) {}

    public function handle(): void
    {
        DB::transaction(function (): void {
            $publication = PagePublication::query()->find($this->publicationId);

            if (! $publication) {
                return;
            }

            $clickedAt = now();
            $publication->clicks()->create([
                'link_key' => $this->linkKey,
                'link_title' => $this->linkTitle,
                'clicked_at' => $clickedAt,
                'referrer_domain' => $this->referrerDomain,
                'user_agent_family' => $this->userAgentFamily,
            ]);

            DailyLinkMetric::query()->insertOrIgnore([
                'page_publication_id' => $publication->id,
                'link_key' => $this->linkKey,
                'metric_date' => $clickedAt->toDateString(),
                'link_title' => $this->linkTitle,
                'clicks' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DailyLinkMetric::query()
                ->where('page_publication_id', $publication->id)
                ->where('link_key', $this->linkKey)
                ->where('metric_date', $clickedAt->toDateString())
                ->increment('clicks');
        });
    }
}
