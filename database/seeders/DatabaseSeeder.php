<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Email fictif temporaire — à remplacer par le vrai email de Khalil (voir docs/SPEC.md §2.5)
        User::updateOrCreate(
            ['email' => 'khalil@khalilshop.sn'],
            [
                'name' => 'Khalil',
                'password' => 'changeme-avant-mise-en-prod',
                'role' => Role::SuperAdmin,
                'email_verified_at' => now(),
            ]
        );

        $this->call(RbacSeeder::class);
    }
}
