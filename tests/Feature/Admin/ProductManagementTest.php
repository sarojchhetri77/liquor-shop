<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('staff can view the product list', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('admin.products.index'))->assertOk();
});

test('customers cannot access the admin panel', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)->get(route('admin.products.index'))->assertForbidden();
});

test('staff can view a product detail page', function () {
    $staff = User::factory()->staff()->create();
    $product = Product::factory()->create();

    $this->actingAs($staff)->get(route('admin.products.show', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/products/Show'));
});

test('staff can create a product with multiple images', function () {
    Storage::fake('public');
    $staff = User::factory()->staff()->create();
    $category = Category::factory()->create();

    $response = $this->actingAs($staff)->post(route('admin.products.store'), [
        'category_id' => $category->id,
        'name' => 'Wireless Headphones',
        'description' => 'Great sound',
        'brand' => 'Sony',
        'price' => 199.99,
        'discount_percent' => 10,
        'stock' => 25,
        'is_active' => true,
        'images' => [
            UploadedFile::fake()->image('one.jpg'),
            UploadedFile::fake()->image('two.jpg'),
        ],
    ]);

    $response->assertRedirect(route('admin.products.index'));

    $product = Product::firstWhere('name', 'Wireless Headphones');
    expect($product)->not->toBeNull()
        ->and($product->slug)->toBe('wireless-headphones')
        ->and($product->images)->toHaveCount(2)
        ->and($product->images->where('is_primary', true))->toHaveCount(1);

    Storage::disk('public')->assertExists($product->images->first()->path);
});

test('final price reflects the discount percentage', function () {
    $product = Product::factory()->create(['price' => 100, 'discount_percent' => 25]);

    expect($product->final_price)->toBe(75.0)
        ->and($product->is_discount_active)->toBeTrue()
        ->and($product->effective_discount_percent)->toBe(25);
});

test('a discount scheduled for the future is not yet applied', function () {
    $product = Product::factory()->create([
        'price' => 100,
        'discount_percent' => 25,
        'discount_starts_at' => now()->addDay(),
    ]);

    expect($product->is_discount_active)->toBeFalse()
        ->and($product->effective_discount_percent)->toBe(0)
        ->and($product->final_price)->toBe(100.0);
});

test('an expired discount is automatically dropped', function () {
    $product = Product::factory()->create([
        'price' => 100,
        'discount_percent' => 25,
        'discount_starts_at' => now()->subWeek(),
        'discount_ends_at' => now()->subDay(),
    ]);

    expect($product->is_discount_active)->toBeFalse()
        ->and($product->effective_discount_percent)->toBe(0)
        ->and($product->final_price)->toBe(100.0);
});

test('a discount within its active window is applied', function () {
    $product = Product::factory()->create([
        'price' => 100,
        'discount_percent' => 25,
        'discount_starts_at' => now()->subDay(),
        'discount_ends_at' => now()->addDay(),
    ]);

    expect($product->is_discount_active)->toBeTrue()
        ->and($product->final_price)->toBe(75.0);
});

test('staff can create a product with a scheduled discount', function () {
    $staff = User::factory()->staff()->create();
    $category = Category::factory()->create();

    $this->actingAs($staff)->post(route('admin.products.store'), [
        'category_id' => $category->id,
        'name' => 'Scheduled Whisky',
        'price' => 100,
        'discount_percent' => 20,
        'discount_starts_at' => '2026-08-01T09:00',
        'discount_ends_at' => '2026-08-31T21:00',
        'stock' => 10,
        'is_active' => true,
    ])->assertRedirect(route('admin.products.index'));

    $product = Product::firstWhere('name', 'Scheduled Whisky');
    expect($product->discount_starts_at->format('Y-m-d H:i'))->toBe('2026-08-01 09:00')
        ->and($product->discount_ends_at->format('Y-m-d H:i'))->toBe('2026-08-31 21:00');
});

test('an expiry date before the start date is rejected', function () {
    $staff = User::factory()->staff()->create();
    $category = Category::factory()->create();

    $this->actingAs($staff)->post(route('admin.products.store'), [
        'category_id' => $category->id,
        'name' => 'Bad Window',
        'price' => 100,
        'discount_percent' => 20,
        'discount_starts_at' => '2026-08-31T09:00',
        'discount_ends_at' => '2026-08-01T09:00',
        'stock' => 10,
        'is_active' => true,
    ])->assertSessionHasErrors('discount_ends_at');
});

test('products can be filtered by name and category', function () {
    $staff = User::factory()->staff()->create();
    $electronics = Category::factory()->create(['name' => 'Electronics']);
    $fashion = Category::factory()->create(['name' => 'Fashion']);

    Product::factory()->create(['name' => 'Gaming Laptop', 'category_id' => $electronics->id]);
    Product::factory()->create(['name' => 'Running Shoes', 'category_id' => $fashion->id]);

    $this->actingAs($staff)
        ->get(route('admin.products.index', ['search' => 'Gaming']))
        ->assertInertia(fn ($page) => $page->has('products.data', 1));

    $this->actingAs($staff)
        ->get(route('admin.products.index', ['category_id' => $fashion->id]))
        ->assertInertia(fn ($page) => $page->has('products.data', 1));
});

test('staff can apply a bulk discount to products', function () {
    $staff = User::factory()->staff()->create();
    $products = Product::factory(3)->create(['discount_percent' => 0]);

    $this->actingAs($staff)->post(route('admin.products.discount'), [
        'product_ids' => $products->pluck('id')->all(),
        'discount_percent' => 30,
    ])->assertRedirect();

    expect(Product::pluck('discount_percent')->unique()->all())->toBe([30]);
});

test('staff can apply a bulk discount with a schedule', function () {
    $staff = User::factory()->staff()->create();
    $products = Product::factory(2)->create(['discount_percent' => 0]);

    $this->actingAs($staff)->post(route('admin.products.discount'), [
        'product_ids' => $products->pluck('id')->all(),
        'discount_percent' => 15,
        'discount_starts_at' => '2026-08-01T09:00',
        'discount_ends_at' => '2026-08-31T21:00',
    ])->assertRedirect();

    $product = $products->first()->fresh();
    expect($product->discount_percent)->toBe(15)
        ->and($product->discount_starts_at->format('Y-m-d H:i'))->toBe('2026-08-01 09:00')
        ->and($product->discount_ends_at->format('Y-m-d H:i'))->toBe('2026-08-31 21:00');
});
