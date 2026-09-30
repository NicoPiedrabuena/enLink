<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardUsernameTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_suggests_a_username_with_letters_and_hyphens_only(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $this->assertMatchesRegularExpression(
            '/^[a-z]+(?:-[a-z]+)*$/',
            $user->linkPage()->firstOrFail()->username,
        );
    }

    public function test_username_is_normalized_to_lowercase_and_rejects_digits(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $page = $user->linkPage()->create([
            'username' => 'mi-enlink',
            'display_name' => $user->name,
            'theme' => ['background' => '#f8fafc', 'primary' => '#111827', 'text' => '#111827', 'radius' => 'rounded'],
        ]);

        $this->actingAs($user)->put('/dashboard/page', [
            'username' => 'Juan-Perez',
            'display_name' => 'Juan Perez',
            'bio' => null,
            'theme' => $page->theme,
        ])->assertSessionHasNoErrors();

        $this->assertSame('juan-perez', $page->refresh()->username);

        $this->actingAs($user)->put('/dashboard/page', [
            'username' => 'juan2',
            'display_name' => 'Juan Perez',
            'bio' => null,
            'theme' => $page->theme,
        ])->assertSessionHasErrors('username');
    }
}
