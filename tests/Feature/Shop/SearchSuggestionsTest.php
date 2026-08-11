<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

test('the base path shared with the frontend is empty at the domain root', function () {
    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page->where('basePath', ''));
});

test('the shared base path covers an app served from a sub-directory', function () {
    URL::forceRootUrl('http://localhost/liquor-shop/public');

    $shared = app(HandleInertiaRequests::class)->share(Request::create('/'));

    expect($shared['basePath'])->toBe('/liquor-shop/public');
});

test('suggestions are returned for a matching product name', function () {
    Product::factory()->create(['name' => 'Glenfiddich 12 Year']);
    Product::factory()->create(['name' => 'Corona Extra']);

    $this->getJson(route('shop.search.suggestions', ['search' => 'glen']))
        ->assertOk()
        ->assertJsonCount(1, 'products')
        ->assertJsonPath('products.0.name', 'Glenfiddich 12 Year');
});

test('suggestions also match the brand name', function () {
    $brand = Brand::factory()->create(['name' => 'Johnnie Walker']);
    Product::factory()->create(['name' => 'Black Label 750ML', 'brand_id' => $brand->id]);
    Product::factory()->create(['name' => 'Something Else']);

    $this->getJson(route('shop.search.suggestions', ['search' => 'johnnie']))
        ->assertOk()
        ->assertJsonCount(1, 'products')
        ->assertJsonPath('products.0.brand', 'Johnnie Walker');
});

test('inactive products are never suggested', function () {
    Product::factory()->inactive()->create(['name' => 'Hidden Whisky']);

    $this->getJson(route('shop.search.suggestions', ['search' => 'hidden']))
        ->assertOk()
        ->assertJsonCount(0, 'products');
});

test('a single character returns no suggestions', function () {
    Product::factory()->create(['name' => 'Whisky']);

    $this->getJson(route('shop.search.suggestions', ['search' => 'w']))
        ->assertOk()
        ->assertJsonCount(0, 'products');
});

test('suggestions are capped at six products', function () {
    Product::factory(8)->create(['name' => 'Tasting Sample']);

    $this->getJson(route('shop.search.suggestions', ['search' => 'tasting']))
        ->assertOk()
        ->assertJsonCount(6, 'products');
});

test('a suggestion carries the details the dropdown renders', function () {
    $product = Product::factory()->create([
        'name' => 'Bombay Sapphire Gin',
        'price' => 100,
        'discount_percent' => 20,
    ]);

    $this->getJson(route('shop.search.suggestions', ['search' => 'bombay']))
        ->assertOk()
        ->assertJsonPath('products.0.slug', $product->slug)
        ->assertJsonPath('products.0.final_price', 80)
        ->assertJsonPath('products.0.is_discount_active', true);
});
