<?php

use App\Models\Product;
use App\Models\Review;
use App\Models\User;

test('a customer can submit a review and it updates the product rating', function () {
    $customer = User::factory()->create();
    $product = Product::factory()->create(['rating' => 0, 'reviews_count' => 0]);

    $this->actingAs($customer)->post(route('shop.reviews.store', $product), [
        'rating' => 4,
        'comment' => 'Really good product!',
    ])->assertRedirect();

    $product->refresh();

    expect((float) $product->rating)->toBe(4.0)
        ->and($product->reviews_count)->toBe(1)
        ->and(Review::where('product_id', $product->id)->where('user_id', $customer->id)->exists())->toBeTrue();
});

test('a second review from the same user updates the existing one', function () {
    $customer = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($customer)->post(route('shop.reviews.store', $product), ['rating' => 2]);
    $this->actingAs($customer)->post(route('shop.reviews.store', $product), ['rating' => 5]);

    expect(Review::where('product_id', $product->id)->count())->toBe(1)
        ->and($product->fresh()->reviews_count)->toBe(1)
        ->and((float) $product->fresh()->rating)->toBe(5.0);
});

test('the average rating is computed across multiple reviewers', function () {
    $product = Product::factory()->create();
    $reviewers = User::factory(2)->create();

    $this->actingAs($reviewers[0])->post(route('shop.reviews.store', $product), ['rating' => 3]);
    $this->actingAs($reviewers[1])->post(route('shop.reviews.store', $product), ['rating' => 5]);

    expect((float) $product->fresh()->rating)->toBe(4.0)
        ->and($product->fresh()->reviews_count)->toBe(2);
});

test('a review requires a rating between 1 and 5', function () {
    $customer = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($customer)->post(route('shop.reviews.store', $product), ['rating' => 9])
        ->assertSessionHasErrors('rating');
});
