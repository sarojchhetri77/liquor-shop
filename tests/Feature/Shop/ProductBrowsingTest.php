<?php

use App\Models\Category;
use App\Models\Product;

test('the storefront home page renders', function () {
    Product::factory(3)->create();

    $this->get(route('home'))->assertOk();
});

test('the product listing can be filtered by name', function () {
    Product::factory()->create(['name' => 'Blue Widget']);
    Product::factory()->create(['name' => 'Red Gadget']);

    $this->get(route('shop.products.index', ['search' => 'Widget']))
        ->assertInertia(fn ($page) => $page->has('products.data', 1));
});

test('the product listing can be filtered by category', function () {
    $target = Category::factory()->create();
    Product::factory()->create(['category_id' => $target->id]);
    Product::factory()->create();

    $this->get(route('shop.products.index', ['category_id' => $target->id]))
        ->assertInertia(fn ($page) => $page->has('products.data', 1));
});

test('inactive products are hidden from the storefront', function () {
    $product = Product::factory()->inactive()->create();

    $this->get(route('shop.products.show', $product))->assertNotFound();
});

test('a product detail page renders for active products', function () {
    $product = Product::factory()->create();

    $this->get(route('shop.products.show', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('shop/ProductDetail'));
});
