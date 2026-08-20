<?php

use App\Models\Promotion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('staff can create a promotion with an image', function () {
    Storage::fake('public');
    $staff = User::factory()->staff()->create();

    $response = $this->actingAs($staff)->post(route('admin.promotions.store'), [
        'title' => 'Weekend Special',
        'link' => '/products',
        'is_active' => true,
        'sort_order' => 0,
        'image' => UploadedFile::fake()->image('promo.jpg'),
    ]);

    $response->assertRedirect(route('admin.promotions.index'));

    $promotion = Promotion::first();
    expect($promotion)->not->toBeNull()
        ->and($promotion->title)->toBe('Weekend Special')
        ->and($promotion->is_active)->toBeTrue();

    Storage::disk('public')->assertExists($promotion->image);
});

test('the active promotion is shared to the homepage popup', function () {
    Promotion::factory()->create(['is_active' => true, 'title' => 'Live promo']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('promotions', 1)
            ->where('promotions.0.title', 'Live promo')
            ->where('promotions.0.is_active', true));
});

test('no popup is shown when there is no active promotion', function () {
    Promotion::factory()->inactive()->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('promotions', 0));
});

test('customers cannot manage promotions', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)->get(route('admin.promotions.index'))->assertForbidden();
});

test('staff can delete a promotion', function () {
    $staff = User::factory()->staff()->create();
    $promotion = Promotion::factory()->create();

    $this->actingAs($staff)->delete(route('admin.promotions.destroy', $promotion))
        ->assertRedirect();

    $this->assertDatabaseCount('promotions', 0);
});
