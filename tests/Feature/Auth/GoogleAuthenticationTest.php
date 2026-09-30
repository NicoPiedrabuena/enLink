<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_account_is_created_verified_and_authenticated(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'name' => 'Persona Google',
            'email' => 'persona@gmail.com',
        ]));

        $this->get('/auth/google/callback')->assertRedirect('/dashboard');

        $user = User::where('email', 'persona@gmail.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_existing_account_is_safely_linked_by_verified_email(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'persona@gmail.com']);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-456',
            'name' => 'Persona Google',
            'email' => 'persona@gmail.com',
        ]));

        $this->get('/auth/google/callback')->assertRedirect('/dashboard');

        $this->assertSame($user->id, auth()->id());
        $this->assertSame('google-456', $user->fresh()->google_id);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
