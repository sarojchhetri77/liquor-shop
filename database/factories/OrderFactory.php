<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 20, 1500);

        return [
            'order_number' => 'ORD-'.strtoupper(Str::random(8)),
            'user_id' => User::factory(),
            'status' => fake()->randomElement(OrderStatus::cases()),
            'payment_method' => 'cod',
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'customer_name' => fake()->name(),
            'contact' => fake()->phoneNumber(),
            'shipping_address' => fake()->address(),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
