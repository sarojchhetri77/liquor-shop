<?php

use App\Models\Product;
use App\Models\User;

test('a product a guest tried to add is added to the cart after they log in', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['is_active' => true, 'stock' => 10]);

    // Guest is redirected to the login page carrying the intended product.
    $this->get('/login?add='.$product->id)->assertOk();

    // Logging in adds the product and sends them to their cart.
    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('shop.cart.index'));

    expect((int) $user->cartItems()->where('product_id', $product->id)->sum('quantity'))->toBe(1);
});

test('logging in without a pending product lands on the default page', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(config('fortify.home'));

    expect($user->fresh()->cartItems()->count())->toBe(0);
});
