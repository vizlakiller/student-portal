<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Safe to run more than once: existing records are kept, not duplicated.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'System Administrator', 'password' => 'password', 'role' => 'admin'],
        );

        User::firstOrCreate(
            ['email' => 'staff@example.com'],
            ['name' => 'Academic Officer', 'password' => 'password', 'role' => 'staff'],
        );

        $this->call([
            ProgrammeSeeder::class,
            SubjectSeeder::class,
            StudentSeeder::class,
        ]);
    }
}
