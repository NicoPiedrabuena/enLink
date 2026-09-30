<?php

namespace Tests\Feature;

use App\Models\LinkPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DashboardEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_reorder_all_of_their_links(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $page = $this->pageFor($user);
        $first = $page->links()->create(['title' => 'Primero', 'url' => 'https://example.com/first', 'position' => 0]);
        $second = $page->links()->create(['title' => 'Segundo', 'url' => 'https://example.com/second', 'position' => 1]);

        $this->actingAs($user)
            ->put('/dashboard/links/reorder', ['links' => [$second->id, $first->id]])
            ->assertRedirect();

        $this->assertSame(0, $second->refresh()->position);
        $this->assertSame(1, $first->refresh()->position);
    }

    public function test_user_cannot_include_another_users_link_in_the_order(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $page = $this->pageFor($user);
        $ownLink = $page->links()->create(['title' => 'Propio', 'url' => 'https://example.com/own']);
        $otherLink = $this->pageFor(User::factory()->create())->links()->create(['title' => 'Ajeno', 'url' => 'https://example.com/other']);

        $this->actingAs($user)
            ->put('/dashboard/links/reorder', ['links' => [$ownLink->id, $otherLink->id]])
            ->assertForbidden();
    }

    public function test_owner_can_upload_a_valid_avatar_to_public_storage(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['email_verified_at' => now()]);
        $page = $this->pageFor($user);

        $this->actingAs($user)
            ->post('/dashboard/avatar', ['avatar' => UploadedFile::fake()->image('avatar.png', 200, 200)])
            ->assertRedirect();

        $path = $page->refresh()->avatar_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_owner_can_create_a_whatsapp_reservation_button(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $page = $this->pageFor($user);

        $this->actingAs($user)->post('/dashboard/links', [
            'title' => 'Reservá tu mesa',
            'icon' => 'calendar',
            'type' => 'reservation',
            'whatsapp_number' => '5491112345678',
        ])->assertRedirect();

        $link = $page->links()->firstOrFail();
        $this->assertSame('reservation', $link->type);
        $this->assertSame('calendar', $link->icon);
        $this->assertSame('5491112345678', $link->settings['whatsapp_number']);
    }

    public function test_reservation_rejects_an_invalid_whatsapp_number(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->pageFor($user);

        $this->actingAs($user)->post('/dashboard/links', [
            'title' => 'Reservar',
            'icon' => 'whatsapp',
            'type' => 'reservation',
            'whatsapp_number' => '+54 11 abc',
        ])->assertSessionHasErrors('whatsapp_number');
    }

    private function pageFor(User $user): LinkPage
    {
        return $user->linkPage()->create([
            'username' => 'pagina-'.str()->lower(str()->random(8)),
            'display_name' => $user->name,
            'theme' => ['background' => '#f8fafc', 'primary' => '#111827', 'text' => '#111827', 'radius' => 'rounded'],
        ]);
    }
}
