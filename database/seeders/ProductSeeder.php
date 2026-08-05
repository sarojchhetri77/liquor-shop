<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Seed the product catalog. Every seeded product ships with real hero
     * photography (stored on the public disk); products without a genuine
     * image are intentionally left out. Depends on CategorySeeder.
     */
    public function run(): void
    {
        /** @var array<string, array<int, array{name: string, brand: string, price: int, image: string}>> $catalog */
        $catalog = [
            'Whisky' => [
                ['name' => "Jack Daniel's Old No.7 750ML", 'brand' => "Jack Daniel's", 'price' => 4200, 'image' => 'products/whisky-1.jpg'],
                ['name' => 'Johnnie Walker Black Label 750ML', 'brand' => 'Johnnie Walker', 'price' => 6500, 'image' => 'products/whisky-2.jpg'],
                ['name' => 'Glenfiddich 12 Year Single Malt 700ML', 'brand' => 'Glenfiddich', 'price' => 9800, 'image' => 'products/whisky-3.jpg'],
            ],
            'Beer' => [
                ['name' => 'Corona Extra 355ML', 'brand' => 'Corona', 'price' => 480, 'image' => 'products/beer-1.jpg'],
            ],
            'Rum & Gin' => [
                ['name' => 'Bombay Sapphire Gin 750ML', 'brand' => 'Bombay Sapphire', 'price' => 5600, 'image' => 'products/gin-1.jpg'],
                ['name' => 'Tanqueray Gin 750ML', 'brand' => 'Tanqueray', 'price' => 5900, 'image' => 'products/gin-2.jpg'],
            ],
            'Wine' => [
                ['name' => "Jacob's Creek Shiraz Cabernet 750ML", 'brand' => "Jacob's Creek", 'price' => 2200, 'image' => 'products/wine-2.jpg'],
                ['name' => 'Fratelli Sangiovese 750ML', 'brand' => 'Fratelli', 'price' => 2400, 'image' => 'products/wine-1.jpg'],
            ],
        ];

        foreach ($catalog as $categoryName => $items) {
            $category = Category::where('name', $categoryName)->firstOrFail();

            foreach ($items as $item) {
                $product = Product::factory()->create([
                    'category_id' => $category->id,
                    'name' => $item['name'],
                    'slug' => Str::slug($item['name']).'-'.fake()->unique()->randomNumber(5),
                    'brand' => $item['brand'],
                    'price' => $item['price'],
                    'discount_percent' => fake()->randomElement([0, 0, 5, 10, 15]),
                    'stock' => fake()->numberBetween(0, 120),
                    'description' => "{$item['name']} — a customer favourite from {$item['brand']}. Enjoy responsibly.",
                ]);

                $product->images()->create([
                    'path' => $item['image'],
                    'is_primary' => true,
                    'sort_order' => 0,
                ]);
            }
        }
    }
}
