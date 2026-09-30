<?php

namespace App\Http\Controllers;

use App\Jobs\RecordPageVisitJob;
use App\Models\LinkPage;
use App\Models\PagePublication;
use App\Support\PublicPageCache;
use App\Support\TrafficContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class PublicPageController extends Controller
{
    public function show(string $username): View
    {
        $username = strtolower($username);

        $publication = Cache::remember(PublicPageCache::key($username), now()->addHour(), function () use ($username): PagePublication {
            return LinkPage::query()
                ->with(['activePublication', 'user:id,status'])
                ->where('status', 'active')
                ->whereHas('user', fn (Builder $query): Builder => $query->where('status', 'active'))
                ->whereHas('activePublication', fn (Builder $query): Builder => $query
                    ->where('username', $username)
                    ->where('status', 'published'))
                ->firstOrFail()
                ->activePublication;
        });

        $userAgent = request()->userAgent();

        if (! TrafficContext::isBot($userAgent)) {
            RecordPageVisitJob::dispatch(
                $publication->id,
                TrafficContext::referrerDomain(request()->headers->get('referer')),
                TrafficContext::userAgentFamily($userAgent),
            );
        }

        return view('public-page', compact('publication'));
    }
}
