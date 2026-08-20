<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a category exposes a browsable url for its stored image', function () {
    $category = Category::factory()->create(['image' => 'categories/whisky.jpg']);

    expect($category->image_url)->toBe(Storage::disk('public')->url('categories/whisky.jpg'))
        ->and($category->image_url)->toStartWith('http');
});

test('a category without an image has a null image url', function () {
    $category = Category::factory()->create(['image' => null]);

    expect($category->image_url)->toBeNull();
});

test('an externally hosted category image is passed through untouched', function () {
    $category = Category::factory()->create(['image' => 'https://cdn.example.com/a.jpg']);

    expect($category->image_url)->toBe('https://cdn.example.com/a.jpg');
});

test('an uploaded category image comes back as a usable url', function () {
    Storage::fake('public');
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post(route('admin.categories.store'), [
        'name' => 'Whisky',
        'image' => UploadedFile::fake()->image('whisky.jpg'),
    ])->assertRedirect();

    $category = Category::firstWhere('name', 'Whisky');

    expect($category->image)->not->toBeNull();
    Storage::disk('public')->assertExists($category->image);

    $this->actingAs($staff)->get(route('admin.categories.index'))
        ->assertInertia(fn ($page) => $page->where('categories.data.0.image_url', $category->image_url));
});
