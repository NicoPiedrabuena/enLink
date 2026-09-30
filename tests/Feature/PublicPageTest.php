<?php

namespace Tests\Feature;

use App\Models\LinkPage;
use App\Models\User;
use App\Support\PublishedLinkToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_publication_is_rendered_without_authentication(): void
    {
        $this->publishedPage();

        $this->get('/casa-publica')
            ->assertOk()
            ->assertSee('<title>Casa Pública · Enlink</title>', false)
            ->assertSee('Un lugar para compartir')
            ->assertSee('/l/', false)
            ->assertDontSee('href="https://example.com/sitio"', false);
    }

    public function test_public_page_uses_the_snapshot_instead_of_unpublished_draft_changes(): void
    {
        $page = $this->publishedPage();
        $page->update(['display_name' => 'Nombre sin publicar']);
        $page->links()->firstOrFail()->update(['title' => 'Enlace sin publicar']);

        $this->get('/casa-publica')
            ->assertOk()
            ->assertSee('Casa Pública')
            ->assertDontSee('Nombre sin publicar')
            ->assertSee('Mi sitio')
            ->assertDontSee('Enlace sin publicar');
    }

    public function test_username_without_an_active_publication_returns_not_found(): void
    {
        $user = User::factory()->create();
        $page = $user->linkPage()->create([
            'username' => 'sin-publicar',
            'display_name' => 'Sin publicar',
            'theme' => $this->theme(),
        ]);
        $page->publications()->create([
            'version' => 1,
            'username' => 'sin-publicar',
            'payload' => $this->payload(),
            'published_at' => now(),
        ]);

        $this->get('/sin-publicar')->assertNotFound();
    }

    public function test_reservation_button_opens_whatsapp_with_the_completed_message(): void
    {
        $page = $this->publishedPage();
        $publication = $page->activePublication;
        $payload = $publication->payload;
        $payload['links'][0]['type'] = 'reservation';
        $payload['links'][0]['settings'] = ['whatsapp_number' => '5491112345678'];
        $payload['links'][0]['url'] = 'https://wa.me/5491112345678';
        DB::table('page_publications')->where('id', $publication->id)->update(['payload' => json_encode($payload)]);

        $token = PublishedLinkToken::encode($publication->id, 1);
        $response = $this->post("/l/{$token}/reservation", ['name' => 'Ana', 'guests' => 4]);

        $response->assertRedirect();
        $this->assertStringStartsWith('https://wa.me/5491112345678?text=', $response->headers->get('Location'));
        $this->assertStringContainsString('Ana', urldecode($response->headers->get('Location')));
        $this->assertStringContainsString('4 personas', urldecode($response->headers->get('Location')));
    }

    private function publishedPage(): LinkPage
    {
        $user = User::factory()->create();
        $page = $user->linkPage()->create([
            'username' => 'casa-publica',
            'display_name' => 'Casa Pública',
            'bio' => 'Un lugar para compartir',
            'theme' => $this->theme(),
        ]);
        $page->links()->create([
            'title' => 'Mi sitio',
            'url' => 'https://example.com/sitio',
            'position' => 0,
        ]);
        $publication = $page->publications()->create([
            'version' => 1,
            'username' => 'casa-publica',
            'payload' => $this->payload(),
            'published_at' => now(),
        ]);
        $page->update(['active_publication_id' => $publication->id]);

        return $page;
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'display_name' => 'Casa Pública',
            'bio' => 'Un lugar para compartir',
            'avatar_path' => null,
            'theme' => $this->theme(),
            'links' => [[
                'id' => 1,
                'title' => 'Mi sitio',
                'url' => 'https://example.com/sitio',
                'position' => 0,
            ]],
        ];
    }

    /** @return array<string, string> */
    private function theme(): array
    {
        return ['background' => '#f8fafc', 'primary' => '#111827', 'text' => '#111827', 'radius' => 'rounded'];
    }
}
