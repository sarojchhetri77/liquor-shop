<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with a liquor-store catalog.
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

        $customer = User::factory()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
        ]);

        $reviewers = User::factory(8)->create()->push($customer);

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
            $category = Category::factory()->create([
                'name' => $categoryName,
                'slug' => Str::slug($categoryName),
                'image' => null,
            ]);

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

                $raters = $reviewers->random(fake()->numberBetween(0, 5));

                foreach ($raters as $rater) {
                    Review::factory()->create([
                        'product_id' => $product->id,
                        'user_id' => $rater->id,
                    ]);
                }

                $aggregate = $product->reviews()
                    ->selectRaw('AVG(rating) as average, COUNT(*) as total')
                    ->first();

                $product->forceFill([
                    'rating' => round((float) ($aggregate->average ?? 0), 2),
                    'reviews_count' => (int) ($aggregate->total ?? 0),
                ])->save();
            }
        }

        $this->seedOrders($reviewers->all());

        Promotion::factory()->create([
            'title' => 'Weekend Special — 15% off all whisky. Enjoy!',
            'link' => '/products?category_id=1',
            'image' => 'https://placehold.co/1000x640/7a1f3d/f4e4c1?text=Nectar+Weekend+Special%0A15%25+OFF+Whisky',
            'is_active' => true,
        ]);

        $this->command->info('Seeded liquor catalog. Admin: admin@example.com / password');
    }

    /**
     * Seed realistic orders spread over the last two weeks so the admin
     * dashboard analytics and charts have meaningful data.
     *
     * @param  array<int, User>  $customers
     */
    private function seedOrders(array $customers): void
    {
        $products = Product::where('is_active', true)->get();

        if ($products->isEmpty() || $customers === []) {
            return;
        }

        $statuses = [
            OrderStatus::Pending,
            OrderStatus::Processing,
            OrderStatus::Shipped,
            OrderStatus::Delivered,
            OrderStatus::Delivered,
            OrderStatus::Delivered,
            OrderStatus::Cancelled,
        ];

        foreach (range(1, 60) as $n) {
            /** @var User $customer */
            $customer = fake()->randomElement($customers);
            $createdAt = now()
                ->subDays(fake()->numberBetween(0, 13))
                ->setTime(fake()->numberBetween(9, 20), fake()->numberBetween(0, 59));

            $order = Order::factory()->for($customer)->create([
                'status' => fake()->randomElement($statuses),
                'customer_name' => $customer->name,
                'contact' => $customer->contact ?? fake()->phoneNumber(),
                'subtotal' => 0,
                'total' => 0,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $subtotal = 0.0;

            foreach ($products->random(fake()->numberBetween(1, 4)) as $product) {
                $quantity = fake()->numberBetween(1, 3);
                $unitPrice = $product->final_price;
                $lineTotal = round($unitPrice * $quantity, 2);
                $subtotal += $lineTotal;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ]);
            }

            $order->update(['subtotal' => $subtotal, 'total' => $subtotal]);
        }
    }
}
