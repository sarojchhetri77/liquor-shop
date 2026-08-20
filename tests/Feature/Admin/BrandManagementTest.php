<?php

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;

test('staff can add a brand from the product form', function () {
    $staff = User::factory()->staff()->create();

    $response = $this->actingAs($staff)
        ->postJson(route('admin.brands.store'), ['name' => 'Old Monk']);

    $response->assertCreated()
        ->assertJsonPath('brand.name', 'Old Monk');

    $brand = Brand::firstWhere('name', 'Old Monk');
    expect($brand)->not->toBeNull()
        ->and($brand->slug)->toBe('old-monk');
});

test('adding a brand that already exists reuses it', function () {
    $staff = User::factory()->staff()->create();
    $existing = Brand::factory()->create(['name' => 'Tuborg']);

    $this->actingAs($staff)
        ->postJson(route('admin.brands.store'), ['name' => 'tuborg'])
        ->assertCreated()
        ->assertJsonPath('brand.id', $existing->id);

    expect(Brand::count())->toBe(1);
});

test('a brand name is required', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->postJson(route('admin.brands.store'), ['name' => ''])
        ->assertJsonValidationErrors('name');
});

test('customers cannot add brands', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->postJson(route('admin.brands.store'), ['name' => 'Sneaky'])
        ->assertForbidden();

    expect(Brand::count())->toBe(0);
});

test('the product form is given the brands on file', function () {
    $staff = User::factory()->staff()->create();
    Brand::factory(2)->create();

    $this->actingAs($staff)->get(route('admin.products.create'))
        ->assertInertia(fn ($page) => $page->has('brands', 2));
});

test('staff can view the brands list', function () {
    $staff = User::factory()->staff()->create();
    Brand::factory(3)->create();

    $this->actingAs($staff)->get(route('admin.brands.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/brands/Index')->has('brands.data', 3));
});

test('the brands list can be filtered by name', function () {
    $staff = User::factory()->staff()->create();
    Brand::factory()->create(['name' => 'Tanqueray']);
    Brand::factory()->create(['name' => 'Corona']);

    $this->actingAs($staff)->get(route('admin.brands.index', ['search' => 'tanq']))
        ->assertInertia(fn ($page) => $page->has('brands.data', 1));
});

test('staff can rename a brand and the slug follows', function () {
    $staff = User::factory()->staff()->create();
    $brand = Brand::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

    $this->actingAs($staff)
        ->put(route('admin.brands.update', $brand), ['name' => 'New Name'])
        ->assertRedirect();

    expect($brand->fresh()->name)->toBe('New Name')
        ->and($brand->fresh()->slug)->toBe('new-name');
});

test('a brand cannot be renamed onto an existing brand', function () {
    $staff = User::factory()->staff()->create();
    Brand::factory()->create(['name' => 'Taken']);
    $brand = Brand::factory()->create(['name' => 'Mine']);

    $this->actingAs($staff)
        ->put(route('admin.brands.update', $brand), ['name' => 'Taken'])
        ->assertSessionHasErrors('name');
});

test('deleting a brand leaves its products without a brand', function () {
    $staff = User::factory()->staff()->create();
    $brand = Brand::factory()->create();
    $product = Product::factory()->create(['brand_id' => $brand->id]);

    $this->actingAs($staff)
        ->delete(route('admin.brands.destroy', $brand))
        ->assertRedirect();

    expect(Brand::count())->toBe(0)
        ->and($product->fresh())->not->toBeNull()
        ->and($product->fresh()->brand_id)->toBeNull();
});

test('creating a brand through the admin page redirects instead of returning json', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->withHeader('X-Inertia', 'true')
        ->post(route('admin.brands.store'), ['name' => 'Inertia Brand'])
        ->assertRedirect();

    expect(Brand::where('name', 'Inertia Brand')->exists())->toBeTrue();
});
