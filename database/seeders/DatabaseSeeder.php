<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminEmail = env('SEED_ADMIN_EMAIL');
        if ($adminEmail) {
            \App\Models\User::where('email', $adminEmail)->update(['role' => 'admin']);
        }
        $this->call([
            ReferenceSeeder::class,
            UruUnitSeeder::class,
            AnnouncementSeeder::class,
        ]);
    }
}
