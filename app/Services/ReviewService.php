<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    /**
     * Create or update the user's review for a product, then refresh the
     * product's cached rating and review count.
     *
     * @param  array{rating: int, comment?: string|null}  $data
     */
    public function submit(User $user, Product $product, array $data): Review
    {
        return DB::transaction(function () use ($user, $product, $data): Review {
            $review = $product->reviews()->updateOrCreate(
                ['user_id' => $user->id],
                ['rating' => $data['rating'], 'comment' => $data['comment'] ?? null],
            );

            $this->recalculateRating($product);

            return $review;
        });
    }

    public function delete(Product $product, Review $review): void
    {
        DB::transaction(function () use ($product, $review): void {
            $review->delete();
            $this->recalculateRating($product);
        });
    }

    /**
     * Recompute the product's average rating and review count from its reviews.
     */
    private function recalculateRating(Product $product): void
    {
        $aggregate = $product->reviews()
            ->reorder()
            ->selectRaw('AVG(rating) as average, COUNT(*) as total')
            ->first();

        $product->forceFill([
            'rating' => round((float) ($aggregate->average ?? 0), 2),
            'reviews_count' => (int) ($aggregate->total ?? 0),
        ])->save();
    }
}
