<?php

namespace App\Jobs;

use App\Models\DailyPageMetric;
use App\Models\PagePublication;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class RecordPageVisitJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $publicationId,
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

            $visitedAt = now();
            $publication->visits()->create([
                'visited_at' => $visitedAt,
                'referrer_domain' => $this->referrerDomain,
                'user_agent_family' => $this->userAgentFamily,
            ]);

            DailyPageMetric::query()->insertOrIgnore([
                'page_publication_id' => $publication->id,
                'metric_date' => $visitedAt->toDateString(),
                'visits' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DailyPageMetric::query()
                ->where('page_publication_id', $publication->id)
                ->where('metric_date', $visitedAt->toDateString())
                ->increment('visits');
        });
    }
}
