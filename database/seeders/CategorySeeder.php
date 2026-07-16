<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * The store's top-level product categories.
     *
     * @var array<int, string>
     */
    public const CATEGORIES = [
        'Whisky',
        'Vodka',
        'Wine',
        'Beer',
        'Rum & Gin',
        'Tequila & Champagne',
    ];

    /**
     * Seed the product categories.
     */
    public function run(): void
    {
        foreach (self::CATEGORIES as $categoryName) {
            Category::factory()->create([
                'name' => $categoryName,
                'slug' => Str::slug($categoryName),
                'image' => null,
            ]);
        }
    }
}
