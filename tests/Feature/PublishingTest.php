<?php

namespace Tests\Feature;

use App\Actions\Credits\CreateCreditTransactionAction;
use App\Models\CreditBalance;
use App\Models\CreditTransaction;
use App\Models\LinkPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class PublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_does_not_grant_free_credits(): void
    {
        $this->post('/register', [
            'name' => 'Nueva Persona',
            'email' => 'nueva@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');

        $user = User::query()->where('email', 'nueva@example.com')->firstOrFail();

        $this->assertNull($user->creditBalance);
        $this->assertDatabaseCount('credit_transactions', 0);
    }

    public function test_first_publication_costs_one_credit_and_creates_an_immutable_snapshot(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $page = $this->pageFor($user);
        $link = $page->links()->create([
            'title' => 'Mi sitio',
            'url' => 'https://example.com',
            'position' => 0,
        ]);
        $this->giveCredits($user, 2);

        $this->actingAs($user)->post('/dashboard/publish')->assertRedirect();

        $publication = $page->refresh()->activePublication;

        $this->assertNotNull($publication);
        $this->assertSame(1, $publication->version);
        $this->assertSame('Pagina de prueba', $publication->payload['display_name']);
        $this->assertSame('Mi sitio', $publication->payload['links'][0]['title']);
        $this->assertSame(1, $user->creditBalance()->firstOrFail()->balance);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'type' => 'publication',
            'amount' => -1,
            'balance_after' => 1,
        ]);

        $page->update(['display_name' => 'Nombre sin publicar']);
        $link->update(['title' => 'Link sin publicar']);

        $this->assertSame('Pagina de prueba', $publication->fresh()->payload['display_name']);
        $this->assertSame('Mi sitio', $publication->fresh()->payload['links'][0]['title']);
    }

    public function test_an_update_without_credits_does_not_create_a_snapshot_or_charge_the_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $page = $this->pageFor($user);
        $page->links()->create(['title' => 'Mi sitio', 'url' => 'https://example.com', 'position' => 0]);
        CreditBalance::create(['user_id' => $user->id, 'balance' => 0]);

        $this->actingAs($user)->post('/dashboard/publish')->assertRedirect('/dashboard/credits');

        $this->assertNull($page->refresh()->active_publication_id);
        $this->assertDatabaseCount('page_publications', 0);
        $this->assertSame(0, $user->creditBalance()->firstOrFail()->balance);
        $this->assertDatabaseCount('credit_transactions', 0);
    }

    public function test_each_publication_creates_a_new_version(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $page = $this->pageFor($user);
        $page->links()->create(['title' => 'Mi sitio', 'url' => 'https://example.com', 'position' => 0]);
        $this->giveCredits($user, 2);

        $this->actingAs($user)->post('/dashboard/publish')->assertRedirect();
        $firstPublication = $page->fresh('activePublication')->activePublication;
        $page->update(['display_name' => 'Página actualizada']);

        $this->actingAs($user)->post('/dashboard/publish')->assertRedirect();
        $secondPublication = $page->fresh('activePublication')->activePublication;

        $this->assertSame(1, $firstPublication->version);
        $this->assertSame(2, $secondPublication->version);
        $this->assertNotSame($firstPublication->id, $secondPublication->id);
        $this->assertSame(0, $user->creditBalance()->firstOrFail()->balance);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'type' => 'publication',
            'amount' => -1,
            'balance_after' => 0,
            'reference_key' => "publication:{$secondPublication->id}",
        ]);
    }

    public function test_credit_ledger_movements_cannot_be_changed_or_deleted(): void
    {
        $user = User::factory()->create();
        $transaction = $this->giveCredits($user, 2);

        $this->expectException(LogicException::class);

        $transaction->update(['description' => 'Cambio no permitido']);
    }

    private function giveCredits(User $user, int $amount): CreditTransaction
    {
        return app(CreateCreditTransactionAction::class)->execute(
            $user,
            'welcome_bonus',
            $amount,
            'Créditos de prueba',
            "test-bonus:{$user->id}",
        );
    }

    private function pageFor(User $user): LinkPage
    {
        return $user->linkPage()->create([
            'username' => 'pagina-'.str()->lower(str()->random(8)),
            'display_name' => 'Pagina de prueba',
            'theme' => ['background' => '#f8fafc', 'primary' => '#111827', 'text' => '#111827', 'radius' => 'rounded'],
        ]);
    }
}
