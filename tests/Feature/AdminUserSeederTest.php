<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_local_admin_without_free_credits(): void
    {
        config()->set('enlink.admin.email', 'administrador@enlink.test');
        config()->set('enlink.admin.password', 'Una-clave-segura-2026');

        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $user = User::query()->where('email', 'administrador@enlink.test')->firstOrFail();

        $this->assertSame('admin', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertSame(0, $user->creditTransactions()->count());
    }

    public function test_it_rejects_an_insecure_admin_password(): void
    {
        config()->set('enlink.admin.email', 'administrador@enlink.test');
        config()->set('enlink.admin.password', 'password');

        $this->expectException(\RuntimeException::class);
        $this->seed(AdminUserSeeder::class);
    }
}
