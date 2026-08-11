<?php

use App\Models\Brand;
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
