<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private ProductService $products) {}

    public function index(Request $request): Response
    {
        return Inertia::render('shop/Products', [
            'products' => $this->products->paginateForShop([
                'search' => $request->string('search')->toString() ?: null,
                'category_id' => $request->integer('category_id') ?: null,
                'sort' => $request->string('sort')->toString() ?: null,
            ]),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'category_id' => $request->integer('category_id') ?: null,
                'sort' => $request->string('sort')->toString(),
            ],
        ]);
    }

    public function show(Request $request, Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product->load(['images', 'category', 'reviews.user']);

        $userReview = $request->user()
            ? $product->reviews->firstWhere('user_id', $request->user()->id)
            : null;

        return Inertia::render('shop/ProductDetail', [
            'product' => $product,
            'userReview' => $userReview,
            'related' => Product::active()
                ->where('category_id', $product->category_id)
                ->whereKeyNot($product->id)
                ->with('images')
                ->take(4)
                ->get(),
        ]);
    }
}
