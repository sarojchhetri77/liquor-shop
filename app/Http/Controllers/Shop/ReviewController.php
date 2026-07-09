<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\ReviewRequest;
use App\Models\Product;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    public function __construct(private ReviewService $reviews) {}

    public function store(ReviewRequest $request, Product $product): RedirectResponse
    {
        $this->reviews->submit($request->user(), $product, $request->validated());
        $this->toast('Thanks for your review!');

        return back();
    }
}
