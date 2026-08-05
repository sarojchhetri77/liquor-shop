<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

test('the admin dashboard renders for day-bucketed ranges with real orders', function (string $range) {
    $admin = User::factory()->admin()->create();

    Order::factory()->count(3)->create([
        'status' => OrderStatus::Delivered,
        'total' => 100,
        'created_at' => now()->subDays(2),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['range' => $range]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/Dashboard'));
})->with(['7d', '14d', '30d']);

test('the admin dashboard renders for month-bucketed ranges with real orders', function (string $range) {
    $admin = User::factory()->admin()->create();

    Order::factory()->create([
        'status' => OrderStatus::Delivered,
        'total' => 250,
        'created_at' => now()->subMonths(2),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['range' => $range]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/Dashboard'));
})->with(['1y', 'all']);
