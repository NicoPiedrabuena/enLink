<?php

namespace App\Http\Controllers;

use App\Models\DailyLinkMetric;
use App\Models\DailyPageMetric;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        $page = $request->user()->linkPage;
        $publicationIds = $page?->publications()->pluck('id') ?? collect();
        $start = CarbonImmutable::today()->subDays(29);

        $pageMetrics = DailyPageMetric::query()
            ->whereIn('page_publication_id', $publicationIds)
            ->whereDate('metric_date', '>=', $start)
            ->get();
        $linkMetrics = DailyLinkMetric::query()
            ->whereIn('page_publication_id', $publicationIds)
            ->whereDate('metric_date', '>=', $start)
            ->get();

        $visitsByDate = $pageMetrics->groupBy(fn ($metric): string => $metric->metric_date->toDateString())
            ->map->sum('visits');
        $clicksByDate = $linkMetrics->groupBy(fn ($metric): string => $metric->metric_date->toDateString())
            ->map->sum('clicks');
        $timeline = collect(range(0, 29))->map(function (int $offset) use ($clicksByDate, $start, $visitsByDate): array {
            $date = $start->addDays($offset)->toDateString();

            return [
                'date' => $date,
                'visits' => (int) ($visitsByDate[$date] ?? 0),
                'clicks' => (int) ($clicksByDate[$date] ?? 0),
            ];
        });
        $links = $linkMetrics
            ->groupBy('link_key')
            ->map(fn ($metrics): array => [
                'key' => $metrics->first()->link_key,
                'title' => $metrics->last()->link_title,
                'clicks' => (int) $metrics->sum('clicks'),
            ])
            ->sortByDesc('clicks')
            ->values();
        $visits = (int) $pageMetrics->sum('visits');
        $clicks = (int) $linkMetrics->sum('clicks');

        return Inertia::render('Analytics', [
            'summary' => [
                'visits' => $visits,
                'clicks' => $clicks,
                'clickRate' => $visits > 0 ? round(($clicks / $visits) * 100, 1) : 0,
            ],
            'timeline' => $timeline,
            'links' => $links,
        ]);
    }
}
