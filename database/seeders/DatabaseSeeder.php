<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with a liquor-store catalog.
     *
     * Each domain is handled by its own dedicated seeder; they are invoked
     * here in dependency order (users → categories → products → reviews →
     * orders → promotions).
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            ReviewSeeder::class,
            OrderSeeder::class,
            PromotionSeeder::class,
        ]);

        $this->command->info('Seeded liquor catalog. Admin: admin@example.com / password');
    }
}
