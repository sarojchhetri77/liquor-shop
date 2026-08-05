<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\PromotionService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(private PromotionService $promotions) {}

    public function index(): Response
    {
        return Inertia::render('shop/Home', [
            'promotion' => $this->promotions->activePopup(),
            'featured' => Product::active()
                ->with(['images', 'category'])
                ->orderByDesc('rating')
                ->take(12)
                ->get(),
            'featuredHasMore' => Product::active()->count() > 12,
            'discounted' => Product::active()
                ->where('discount_percent', '>', 0)
                ->with(['images', 'category'])
                ->latest()
                ->take(4)
                ->get(),
            'categories' => Category::withCount('products')->has('products')->orderBy('name')->get(),
        ]);
    }
}
