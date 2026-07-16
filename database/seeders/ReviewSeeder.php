<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Seed product reviews from the customer pool and refresh each product's
     * cached rating and reviews_count aggregates. Depends on UserSeeder and
     * ProductSeeder.
     */
    public function run(): void
    {
        $reviewers = User::where('role', UserRole::Customer)->get();

        if ($reviewers->isEmpty()) {
            return;
        }

        foreach (Product::all() as $product) {
            $raters = $reviewers->random(fake()->numberBetween(0, min(5, $reviewers->count())));

            foreach ($raters as $rater) {
                Review::factory()->create([
                    'product_id' => $product->id,
                    'user_id' => $rater->id,
                ]);
            }

            $aggregate = $product->reviews()
                ->selectRaw('AVG(rating) as average, COUNT(*) as total')
                ->first();

            $product->forceFill([
                'rating' => round((float) ($aggregate->average ?? 0), 2),
                'reviews_count' => (int) ($aggregate->total ?? 0),
            ])->save();
        }
    }
}
