<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Seed realistic orders spread over the last two weeks so the admin
     * dashboard analytics and charts have meaningful data. Depends on
     * UserSeeder and ProductSeeder.
     */
    public function run(): void
    {
        $products = Product::where('is_active', true)->get();
        $customers = User::where('role', UserRole::Customer)->get();

        if ($products->isEmpty() || $customers->isEmpty()) {
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
            $customer = $customers->random();
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
