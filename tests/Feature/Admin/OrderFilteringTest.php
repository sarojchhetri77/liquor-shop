<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

test('staff can search orders by order number', function () {
    $staff = User::factory()->staff()->create();
    Order::factory()->create(['order_number' => 'ORD-FINDME9999']);
    Order::factory()->create(['order_number' => 'ORD-OTHER00000']);

    $this->actingAs($staff)
        ->get(route('admin.orders.index', ['search' => 'FINDME']))
        ->assertInertia(fn ($page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.order_number', 'ORD-FINDME9999'));
});

test('staff can search orders by customer name', function () {
    $staff = User::factory()->staff()->create();
    Order::factory()->create(['customer_name' => 'Ram Bahadur']);
    Order::factory()->create(['customer_name' => 'Sita Kumari']);

    $this->actingAs($staff)
        ->get(route('admin.orders.index', ['search' => 'Ram']))
        ->assertInertia(fn ($page) => $page->has('orders.data', 1));
});

test('staff can filter orders by date range', function () {
    $staff = User::factory()->staff()->create();
    Order::factory()->create(['created_at' => now()->subDays(10)]);
    $recent = Order::factory()->create(['created_at' => now()->subDay()]);

    $this->actingAs($staff)
        ->get(route('admin.orders.index', [
            'date_from' => now()->subDays(2)->toDateString(),
            'date_to' => now()->toDateString(),
        ]))
        ->assertInertia(fn ($page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $recent->id));
});

test('search and status filters combine', function () {
    $staff = User::factory()->staff()->create();
    Order::factory()->create(['customer_name' => 'Ram Bahadur', 'status' => OrderStatus::Pending]);
    Order::factory()->create(['customer_name' => 'Ram Bahadur', 'status' => OrderStatus::Delivered]);

    $this->actingAs($staff)
        ->get(route('admin.orders.index', ['search' => 'Ram', 'status' => 'pending']))
        ->assertInertia(fn ($page) => $page->has('orders.data', 1));
});
