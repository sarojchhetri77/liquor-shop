<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed the store's users: an admin, a staff member, a known test
     * customer, and a pool of additional customers used as reviewers and
     * order placers by the other seeders.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        User::factory()->staff()->create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
        ]);

        User::factory()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
        ]);

        User::factory(8)->create();
    }
}
