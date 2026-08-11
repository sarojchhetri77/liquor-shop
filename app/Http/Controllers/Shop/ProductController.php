<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
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

    /**
     * As-you-type search suggestions for the storefront header. Returns a short
     * list of matching products as JSON rather than a full Inertia page.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $search = trim($request->string('search')->toString());

        if (mb_strlen($search) < 2) {
            return response()->json(['products' => []]);
        }

        $products = $this->products->suggest($search)->map(fn (Product $product): array => [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'brand' => $product->brand?->name,
            'category' => $product->category->name,
            'final_price' => $product->final_price,
            'price' => $product->price,
            'is_discount_active' => $product->is_discount_active,
            'image' => $product->images->first()?->url,
        ]);

        return response()->json(['products' => $products]);
    }

    public function show(Request $request, Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product->load(['images', 'category', 'brand', 'reviews.user']);

        $userReview = $request->user()
            ? $product->reviews->firstWhere('user_id', $request->user()->id)
            : null;

        return Inertia::render('shop/ProductDetail', [
            'product' => $product,
            'userReview' => $userReview,
            'related' => Product::active()
                ->where('category_id', $product->category_id)
                ->whereKeyNot($product->id)
                ->with(['images', 'brand'])
                ->take(4)
                ->get(),
        ]);
    }
}
