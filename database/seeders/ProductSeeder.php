<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Seed the product catalog, grouped by category, and attach real hero
     * photography to a handful of flagship products. Depends on the
     * categories created by CategorySeeder.
     */
    public function run(): void
    {
        /** @var array<string, array<int, array{0: string, 1: string, 2: int}>> $catalog */
        $catalog = [
            'Whisky' => [
                ["Jack Daniel's Old No.7 750ML", "Jack Daniel's", 4200],
                ['Johnnie Walker Black Label 750ML', 'Johnnie Walker', 6500],
                ['Glenfiddich 12 Year Single Malt 700ML', 'Glenfiddich', 9800],
                ['Chivas Regal 12 Year 750ML', 'Chivas Regal', 6200],
                ['Jameson Irish Whiskey 750ML', 'Jameson', 4800],
                ["Ballantine's Finest 750ML", "Ballantine's", 3900],
                ['The Glenlivet Founders Reserve 700ML', 'The Glenlivet', 9200],
                ['100 Pipers Deluxe 750ML', '100 Pipers', 2600],
            ],
            'Vodka' => [
                ['Absolut Original 750ML', 'Absolut', 3200],
                ['Smirnoff No.21 750ML', 'Smirnoff', 2400],
                ['Grey Goose 700ML', 'Grey Goose', 8500],
                ['Ketel One 750ML', 'Ketel One', 5200],
                ['Ciroc Premium 750ML', 'Ciroc', 7800],
                ['Russian Standard 750ML', 'Russian Standard', 3600],
            ],
            'Wine' => [
                ["Jacob's Creek Shiraz Cabernet 750ML", "Jacob's Creek", 2200],
                ['Sula Cabernet Shiraz 750ML', 'Sula Vineyards', 1900],
                ['Yellow Tail Chardonnay 750ML', 'Yellow Tail', 2600],
                ['Fratelli Sangiovese 750ML', 'Fratelli', 2400],
                ['Big Banyan Merlot 750ML', 'Big Banyan', 1700],
                ['Jacob’s Creek Moscato 750ML', "Jacob's Creek", 2300],
            ],
            'Beer' => [
                ['Barahsinghe Craft Lager 650ML', 'Barahsinghe', 380],
                ['Gorkha Premium 650ML', 'Gorkha', 320],
                ['Tuborg Strong 500ML', 'Tuborg', 300],
                ['Carlsberg 500ML', 'Carlsberg', 340],
                ['Heineken 330ML', 'Heineken', 420],
                ['Corona Extra 355ML', 'Corona', 480],
            ],
            'Rum & Gin' => [
                ['Bacardi Carta Blanca 750ML', 'Bacardi', 2900],
                ['Old Monk Rum 750ML', 'Old Monk', 1800],
                ['Captain Morgan Spiced Gold 750ML', 'Captain Morgan', 3100],
                ['Bombay Sapphire Gin 750ML', 'Bombay Sapphire', 5600],
                ['Gordon’s London Dry Gin 750ML', "Gordon's", 3400],
                ['Tanqueray Gin 750ML', 'Tanqueray', 5900],
            ],
            'Tequila & Champagne' => [
                ['Jose Cuervo Especial Gold 750ML', 'Jose Cuervo', 5400],
                ['Camino Real Tequila 750ML', 'Camino Real', 4200],
                ['Moet & Chandon Imperial 750ML', 'Moet & Chandon', 12500],
                ['Chandon Brut 750ML', 'Chandon', 4600],
            ],
        ];

        // Real product photography (stored on the public disk) for a handful of
        // hero products. Everything else falls back to the branded BottleThumb.
        $productImages = [
            "Jack Daniel's Old No.7 750ML" => 'products/whisky-1.jpg',
            'Johnnie Walker Black Label 750ML' => 'products/whisky-2.jpg',
            'Glenfiddich 12 Year Single Malt 700ML' => 'products/whisky-3.jpg',
            'Corona Extra 355ML' => 'products/beer-1.jpg',
            'Bombay Sapphire Gin 750ML' => 'products/gin-1.jpg',
            'Tanqueray Gin 750ML' => 'products/gin-2.jpg',
            'Jacob\'s Creek Shiraz Cabernet 750ML' => 'products/wine-2.jpg',
            'Fratelli Sangiovese 750ML' => 'products/wine-1.jpg',
        ];

        foreach ($catalog as $categoryName => $items) {
            $category = Category::where('name', $categoryName)->firstOrFail();

            foreach ($items as [$name, $brand, $price]) {
                $product = Product::factory()->create([
                    'category_id' => $category->id,
                    'name' => $name,
                    'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(5),
                    'brand' => $brand,
                    'price' => $price,
                    'discount_percent' => fake()->randomElement([0, 0, 0, 5, 10, 15, 20]),
                    'stock' => fake()->numberBetween(0, 120),
                    'description' => "{$name} — a customer favourite from {$brand}. Enjoy responsibly.",
                ]);

                if (isset($productImages[$name])) {
                    $product->images()->create([
                        'path' => $productImages[$name],
                        'is_primary' => true,
                        'sort_order' => 0,
                    ]);
                }
            }
        }
    }
}
