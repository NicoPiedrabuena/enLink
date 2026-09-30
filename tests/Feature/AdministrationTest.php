<?php

namespace Tests\Feature;

use App\Models\CreditBalance;
use App\Models\LinkPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_access_administration(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_suspend_a_public_page_with_an_audit_record(): void
    {
        $admin = $this->admin();
        $page = $this->publishedPage();

        $this->get('/'.$page->username)->assertOk();

        $this->actingAs($admin)->patch("/admin/pages/{$page->id}/status", [
            'status' => 'suspended',
            'reason' => 'Contenido reportado',
        ])->assertRedirect();

        $this->assertSame('suspended', $page->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'page.status_changed',
            'subject_id' => $page->id,
            'reason' => 'Contenido reportado',
        ]);
        $this->get('/'.$page->username)->assertNotFound();
    }

    public function test_admin_credit_adjustment_updates_ledger_and_audit_log(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        CreditBalance::query()->create(['user_id' => $user->id, 'balance' => 0]);

        $this->actingAs($admin)->post("/admin/users/{$user->id}/credit-adjustments", [
            'amount' => 7,
            'reason' => 'Compensación de soporte',
        ])->assertRedirect();

        $this->assertSame(7, $user->creditBalance()->firstOrFail()->balance);
        $this->assertDatabaseHas('credit_transactions', ['user_id' => $user->id, 'type' => 'admin_adjustment', 'amount' => 7]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'credits.adjusted', 'subject_id' => $user->id]);
    }

    public function test_admin_can_create_an_active_package_visible_to_users(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/credit-packages', [
            'name' => 'Paquete inicial',
            'credits' => 10,
            'price' => 1500,
            'currency' => 'ARS',
            'is_active' => true,
            'sort_order' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('credit_packages', ['name' => 'Paquete inicial', 'credits' => 10, 'is_active' => true]);
        $this->actingAs(User::factory()->create(['email_verified_at' => now()]))
            ->get('/dashboard/credits')
            ->assertOk();
    }

    public function test_suspended_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['email' => 'suspended@example.com', 'password' => 'password', 'status' => 'suspended']);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active', 'email_verified_at' => now()]);
    }

    private function publishedPage(): LinkPage
    {
        $user = User::factory()->create();
        $page = $user->linkPage()->create([
            'username' => 'pagina-moderada',
            'display_name' => 'Página moderada',
            'theme' => ['background' => '#fff', 'primary' => '#111', 'text' => '#111', 'radius' => 'rounded'],
        ]);
        $publication = $page->publications()->create([
            'version' => 1,
            'username' => $page->username,
            'payload' => ['display_name' => 'Página moderada', 'bio' => null, 'avatar_path' => null, 'theme' => $page->theme, 'links' => [['id' => 1, 'title' => 'Sitio', 'url' => 'https://example.com', 'position' => 0]]],
            'published_at' => now(),
        ]);
        $page->update(['active_publication_id' => $publication->id]);

        return $page;
    }
}
