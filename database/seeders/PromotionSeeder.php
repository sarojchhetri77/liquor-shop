<?php

namespace Database\Seeders;

use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    /**
     * Seed the homepage promotional banner.
     */
    public function run(): void
    {
        Promotion::factory()->create([
            'title' => 'Weekend Special — 15% off all whisky. Enjoy!',
            'link' => '/products?category_id=1',
            'image' => 'https://placehold.co/1000x640/171717/f5f5f5?text=PB+Store+Weekend+Special%0A15%25+OFF+Whisky',
            'is_active' => true,
        ]);
    }
}
