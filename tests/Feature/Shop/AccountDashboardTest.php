<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

test('the account dashboard shows the customer profile, stats and recent orders', function () {
    $user = User::factory()->create();

    Order::factory()->count(2)->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Delivered,
        'total' => 100,
    ]);
    Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
        'total' => 50,
    ]);
    Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Cancelled,
        'total' => 999,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('profile.email', $user->email)
            ->where('stats.orders', 4)
            ->where('stats.pending', 1)
            ->has('recentOrders', 4));
});

test('the account dashboard only reflects the current user orders', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Order::factory()->create(['user_id' => $user->id, 'total' => 100, 'status' => OrderStatus::Delivered]);
    Order::factory()->count(3)->create(['user_id' => $other->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('stats.orders', 1)->has('recentOrders', 1));
});
