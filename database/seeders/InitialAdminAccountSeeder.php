<?php

namespace Database\Seeders;

use App\Models\AdminAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialAdminAccountSeeder extends Seeder
{
    public function run(): void
    {
        $username = trim((string) config('admin.bootstrap.username'));
        $password = (string) config('admin.bootstrap.password');

        if ($username === '' || $password === '') {
            $this->command?->warn('Initial admin account was skipped: set ADMIN_BOOTSTRAP_USERNAME and ADMIN_BOOTSTRAP_PASSWORD first.');

            return;
        }

        AdminAccount::query()->updateOrCreate(
            ['username' => $username],
            [
                'name' => (string) config('admin.bootstrap.name'),
                'email' => config('admin.bootstrap.email') ?: null,
                'password' => Hash::make($password),
                'role' => 'super_admin',
                'is_active' => true,
            ],
        );
    }
}
