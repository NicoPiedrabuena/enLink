<?php

namespace Tests\Feature;

use App\Jobs\RecordLinkClickJob;
use App\Jobs\RecordPageVisitJob;
use App\Models\DailyLinkMetric;
use App\Models\DailyPageMetric;
use App\Models\LinkPage;
use App\Models\PagePublication;
use App\Models\User;
use App\Support\PublishedLinkToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_visits_and_link_clicks_update_daily_metrics(): void
    {
        [, $publication] = $this->publishedPage();

        (new RecordPageVisitJob($publication->id, 'example.com', 'Chrome'))->handle();
        (new RecordPageVisitJob($publication->id, null, 'Firefox'))->handle();
        (new RecordLinkClickJob($publication->id, '1', 'Mi sitio', null, 'Chrome'))->handle();
        (new RecordLinkClickJob($publication->id, '1', 'Mi sitio', null, 'Chrome'))->handle();

        $this->assertSame(2, $publication->visits()->count());
        $this->assertSame(2, $publication->clicks()->count());
        $this->assertSame(2, DailyPageMetric::query()->value('visits'));
        $this->assertSame(2, DailyLinkMetric::query()->value('clicks'));
        $this->assertFalse(Schema::hasColumn('page_visits', 'ip_address'));
    }

    public function test_public_page_queues_a_visit_and_uses_an_internal_click_url(): void
    {
        Queue::fake();
        [$page] = $this->publishedPage();

        $response = $this->withHeader('User-Agent', 'Mozilla/5.0 Chrome/140.0')
            ->get('/'.$page->username);

        $response->assertOk()->assertSee('/l/', false);
        Queue::assertPushed(RecordPageVisitJob::class);
    }

    public function test_valid_click_redirects_to_snapshot_url_and_queues_tracking(): void
    {
        Queue::fake();
        [, $publication] = $this->publishedPage();
        $token = PublishedLinkToken::encode($publication->id, 1);

        $this->withHeader('User-Agent', 'Mozilla/5.0 Firefox/130.0')
            ->get('/l/'.$token)
            ->assertRedirect('https://example.com/sitio');

        Queue::assertPushed(RecordLinkClickJob::class, fn (RecordLinkClickJob $job): bool => $job->publicationId === $publication->id && $job->linkTitle === 'Mi sitio');
    }

    public function test_tampered_click_token_is_rejected(): void
    {
        [, $publication] = $this->publishedPage();
        $token = PublishedLinkToken::encode($publication->id, 1);

        $this->get('/l/'.$token.'x')->assertNotFound();
    }

    /** @return array{LinkPage, PagePublication} */
    private function publishedPage(): array
    {
        $user = User::factory()->create();
        $page = $user->linkPage()->create([
            'username' => 'pagina-analitica',
            'display_name' => 'Página analítica',
            'theme' => ['background' => '#ffffff', 'primary' => '#111827', 'text' => '#111827', 'radius' => 'rounded'],
        ]);
        $publication = $page->publications()->create([
            'version' => 1,
            'username' => $page->username,
            'payload' => [
                'display_name' => 'Página analítica',
                'bio' => null,
                'avatar_path' => null,
                'theme' => $page->theme,
                'links' => [['id' => 1, 'title' => 'Mi sitio', 'url' => 'https://example.com/sitio', 'position' => 0]],
            ],
            'published_at' => now(),
        ]);
        $page->update(['active_publication_id' => $publication->id]);

        return [$page, $publication];
    }
}
