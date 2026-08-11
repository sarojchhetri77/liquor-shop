<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DiscountRequest;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private ProductService $products) {}

    public function index(Request $request): Response
    {
        return Inertia::render('admin/products/Index', [
            'products' => $this->products->paginateForAdmin([
                'search' => $request->string('search')->toString() ?: null,
                'category_id' => $request->integer('category_id') ?: null,
            ]),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'category_id' => $request->integer('category_id') ?: null,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/products/Form', [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Product $product): Response
    {
        return Inertia::render('admin/products/Show', [
            'product' => $product->load(['images', 'category', 'brand', 'reviews.user']),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $this->products->create($request->validated());
        $this->toast('Product created.');

        return to_route('admin.products.index');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('admin/products/Form', [
            'product' => $product->load(['images', 'brand']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update($product, $request->validated());
        $this->toast('Product updated.');

        return to_route('admin.products.index');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->products->delete($product);
        $this->toast('Product deleted.');

        return back();
    }

    /**
     * Apply a discount to one or many products at once.
     */
    public function applyDiscount(DiscountRequest $request): RedirectResponse
    {
        $count = $this->products->applyBulkDiscount(
            $request->validated('product_ids'),
            (int) $request->validated('discount_percent'),
            $request->validated('discount_starts_at'),
            $request->validated('discount_ends_at'),
        );
        $this->toast("Discount applied to {$count} product(s).");

        return back();
    }
}
