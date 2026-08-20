<?php

use App\Models\Promotion;
use App\Models\User;

test('every active promotion is sent to the storefront', function () {
    Promotion::factory(3)->create(['is_active' => true]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('promotions', 3));
});

test('hidden promotions are kept off the storefront', function () {
    Promotion::factory(2)->create(['is_active' => true]);
    Promotion::factory(3)->create(['is_active' => false]);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page->has('promotions', 2));
});

test('promotions reach the storefront in display order', function () {
    Promotion::factory()->create(['title' => 'Third', 'sort_order' => 3, 'is_active' => true]);
    Promotion::factory()->create(['title' => 'First', 'sort_order' => 1, 'is_active' => true]);
    Promotion::factory()->create(['title' => 'Second', 'sort_order' => 2, 'is_active' => true]);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('promotions.0.title', 'First')
            ->where('promotions.1.title', 'Second')
            ->where('promotions.2.title', 'Third'));
});

test('the storefront copes with no promotions at all', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('promotions', 0));
});

test('the admin promotions table lists active and hidden promotions alike', function () {
    $staff = User::factory()->staff()->create();
    Promotion::factory(2)->create(['is_active' => true]);
    Promotion::factory()->create(['is_active' => false]);

    $this->actingAs($staff)->get(route('admin.promotions.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/promotions/Index')
            ->has('promotions.data', 3));
});
