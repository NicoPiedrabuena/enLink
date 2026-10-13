<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddCreditsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_credits_to_an_account_found_by_email(): void
    {
        $user = User::factory()->create(['email' => 'cliente@example.com']);

        $this->artisan('enlink:credits:add', [
            'email' => 'CLIENTE@example.com',
            'credits' => 7,
            '--reason' => 'Compensación de soporte',
        ])
            ->expectsOutput('Se acreditaron 7 créditos a cliente@example.com. Saldo actual: 7.')
            ->assertExitCode(0);

        $this->assertSame(7, $user->creditBalance()->firstOrFail()->balance);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'type' => 'admin_adjustment',
            'amount' => 7,
            'balance_after' => 7,
            'description' => 'Compensación de soporte',
        ]);
    }

    public function test_it_rejects_zero_or_negative_credit_amounts(): void
    {
        $user = User::factory()->create();

        $this->artisan('enlink:credits:add', [
            'email' => $user->email,
            'credits' => 0,
        ])
            ->expectsOutput('La cantidad de créditos debe ser un número entero entre 1 y 10000.')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('credit_transactions', ['user_id' => $user->id]);
    }
}
