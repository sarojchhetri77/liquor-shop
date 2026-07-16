<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

test('a customer can cancel their pending order within the window', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create(['status' => OrderStatus::Pending]);

    $this->actingAs($customer)
        ->post(route('shop.orders.cancel', $order))
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});

test('cancelling restores the product stock', function () {
    $customer = User::factory()->create();
    $product = Product::factory()->create(['stock' => 3]);
    $order = Order::factory()->for($customer)->create(['status' => OrderStatus::Pending]);
    $order->items()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'unit_price' => 100,
        'quantity' => 2,
        'line_total' => 200,
    ]);

    $this->actingAs($customer)->post(route('shop.orders.cancel', $order));

    expect($product->fresh()->stock)->toBe(5);
});

test('an order cannot be cancelled after the window has passed', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create([
        'status' => OrderStatus::Pending,
        'created_at' => now()->subMinutes(Order::CANCEL_WINDOW_MINUTES + 1),
    ]);

    $this->actingAs($customer)
        ->post(route('shop.orders.cancel', $order))
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

test('a non-pending order cannot be cancelled even within the window', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create(['status' => OrderStatus::Shipped]);

    $this->actingAs($customer)->post(route('shop.orders.cancel', $order));

    expect($order->fresh()->status)->toBe(OrderStatus::Shipped);
});

test('a customer cannot cancel another customer\'s order', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $order = Order::factory()->for($owner)->create(['status' => OrderStatus::Pending]);

    $this->actingAs($other)
        ->post(route('shop.orders.cancel', $order))
        ->assertForbidden();
});

test('the cancel window flags are exposed on the order', function () {
    $customer = User::factory()->create();

    $fresh = Order::factory()->for($customer)->create(['status' => OrderStatus::Pending]);
    $stale = Order::factory()->for($customer)->create([
        'status' => OrderStatus::Pending,
        'created_at' => now()->subMinutes(Order::CANCEL_WINDOW_MINUTES + 1),
    ]);

    expect($fresh->can_cancel)->toBeTrue()
        ->and($fresh->cancellable_until)->not->toBeNull()
        ->and($stale->can_cancel)->toBeFalse();
});
