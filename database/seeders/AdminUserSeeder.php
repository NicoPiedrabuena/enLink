<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the local administrator account.
     */
    public function run(): void
    {
        $email = config('enlink.admin.email');
        $password = config('enlink.admin.password');

        if (blank($email) && blank($password) && ! app()->environment('production')) {
            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('ENLINK_ADMIN_EMAIL debe contener un correo válido.');
        }

        if (! is_string($password) || strlen($password) < 12 || strtolower($password) === 'password') {
            throw new RuntimeException('ENLINK_ADMIN_PASSWORD debe tener al menos 12 caracteres y no puede ser una contraseña de ejemplo.');
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin',
                'email_verified_at' => now(),
                'password' => $password,
                'role' => 'admin',
                'status' => 'active',
            ],
        );
    }
}
