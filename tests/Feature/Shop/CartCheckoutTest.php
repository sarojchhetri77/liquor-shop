<?php

use App\Enums\OrderStatus;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

test('a customer can add a product to their cart', function () {
    $customer = User::factory()->create();
    $product = Product::factory()->create(['stock' => 10]);

    $this->actingAs($customer)->post(route('shop.cart.store', $product), ['quantity' => 2])
        ->assertRedirect();

    $this->assertDatabaseHas('cart_items', [
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
});

test('adding the same product again increments the quantity', function () {
    $customer = User::factory()->create();
    $product = Product::factory()->create(['stock' => 10]);

    $this->actingAs($customer)->post(route('shop.cart.store', $product), ['quantity' => 1]);
    $this->actingAs($customer)->post(route('shop.cart.store', $product), ['quantity' => 3]);

    expect(CartItem::where('user_id', $customer->id)->where('product_id', $product->id)->value('quantity'))
        ->toBe(4);
});

test('guests cannot add to the cart', function () {
    $product = Product::factory()->create();

    $this->post(route('shop.cart.store', $product), ['quantity' => 1])
        ->assertRedirect(route('login'));
});

test('a customer can place a cash on delivery order from the cart', function () {
    $customer = User::factory()->create();
    $product = Product::factory()->create(['price' => 50, 'discount_percent' => 10, 'stock' => 5]);
    CartItem::create(['user_id' => $customer->id, 'product_id' => $product->id, 'quantity' => 2]);

    $response = $this->actingAs($customer)->post(route('shop.checkout.store'), [
        'customer_name' => 'Jane Doe',
        'contact' => '+1 555 999 0000',
        'shipping_address' => '123 Main St, Springfield',
        'note' => 'Leave at the door',
    ]);

    $order = Order::first();
    $response->assertRedirect(route('shop.orders.show', $order));

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->payment_method)->toBe('cod')
        ->and((float) $order->total)->toBe(90.0)
        ->and($order->items)->toHaveCount(1);

    // Cart is emptied and stock is decremented after checkout.
    $this->assertDatabaseCount('cart_items', 0);
    expect($product->fresh()->stock)->toBe(3);
});

test('checkout fails when the cart is empty', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)->post(route('shop.checkout.store'), [
        'customer_name' => 'Jane Doe',
        'contact' => '+1 555 999 0000',
        'shipping_address' => '123 Main St',
    ])->assertSessionHasErrors('cart');
});

test('a customer cannot view another customers order', function () {
    $customer = User::factory()->create();
    $other = User::factory()->create();
    $order = Order::factory()->for($other)->create();

    $this->actingAs($customer)->get(route('shop.orders.show', $order))->assertForbidden();
});
