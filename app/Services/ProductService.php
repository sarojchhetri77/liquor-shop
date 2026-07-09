<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * Paginate products for the admin panel with name / category filtering.
     *
     * @param  array{search?: string|null, category_id?: int|string|null}  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginateForAdmin(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->with(['category', 'images'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Paginate active products for the storefront with name / category filtering.
     *
     * @param  array{search?: string|null, category_id?: int|string|null, sort?: string|null}  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginateForShop(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $query = $this->filteredQuery($filters)
            ->active()
            ->with(['category', 'images']);

        match ($filters['sort'] ?? null) {
            'price_low' => $query->orderBy('price'),
            'price_high' => $query->orderByDesc('price'),
            'rating' => $query->orderByDesc('rating'),
            default => $query->latest(),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Build a query filtered by product name and category.
     *
     * @param  array{search?: string|null, category_id?: int|string|null}  $filters
     * @return \Illuminate\Database\Eloquent\Builder<Product>
     */
    private function filteredQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        return Product::query()
            ->when(
                $filters['search'] ?? null,
                fn ($query, $search) => $query->where('name', 'like', "%{$search}%")
            )
            ->when(
                $filters['category_id'] ?? null,
                fn ($query, $categoryId) => $query->where('category_id', $categoryId)
            );
    }

    /**
     * @param  array{category_id: int, name: string, description?: string|null, brand?: string|null, price: numeric, discount_percent?: int|null, stock?: int|null, is_active?: bool, images?: array<int, UploadedFile>|null}  $data
     */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            $product = Product::create([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'description' => $data['description'] ?? null,
                'brand' => $data['brand'] ?? null,
                'price' => $data['price'],
                'discount_percent' => $data['discount_percent'] ?? 0,
                'stock' => $data['stock'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->syncNewImages($product, $data['images'] ?? []);

            return $product->load('images');
        });
    }

    /**
     * @param  array{category_id: int, name: string, description?: string|null, brand?: string|null, price: numeric, discount_percent?: int|null, stock?: int|null, is_active?: bool, images?: array<int, UploadedFile>|null, removed_image_ids?: array<int, int>|null}  $data
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $product->update([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'brand' => $data['brand'] ?? null,
                'price' => $data['price'],
                'discount_percent' => $data['discount_percent'] ?? 0,
                'stock' => $data['stock'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->removeImages($product, $data['removed_image_ids'] ?? []);
            $this->syncNewImages($product, $data['images'] ?? []);
            $this->ensurePrimaryImage($product);

            return $product->load('images');
        });
    }

    /**
     * Apply a discount percentage to a single product.
     */
    public function applyDiscount(Product $product, int $discountPercent): Product
    {
        $product->update(['discount_percent' => max(0, min(100, $discountPercent))]);

        return $product;
    }

    /**
     * Apply a discount to many products at once (bulk discount tool).
     *
     * @param  array<int, int>  $productIds
     */
    public function applyBulkDiscount(array $productIds, int $discountPercent): int
    {
        return Product::whereIn('id', $productIds)
            ->update(['discount_percent' => max(0, min(100, $discountPercent))]);
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            foreach ($product->images as $image) {
                $this->deleteImageFile($image);
            }

            $product->delete();
        });
    }

    /**
     * @param  array<int, UploadedFile>  $images
     */
    private function syncNewImages(Product $product, array $images): void
    {
        $existingCount = $product->images()->count();

        foreach (array_values($images) as $index => $image) {
            if (! $image instanceof UploadedFile) {
                continue;
            }

            $product->images()->create([
                'path' => $image->store('products', 'public'),
                'is_primary' => $existingCount === 0 && $index === 0,
                'sort_order' => $existingCount + $index,
            ]);
        }
    }

    /**
     * @param  array<int, int>  $imageIds
     */
    private function removeImages(Product $product, array $imageIds): void
    {
        if ($imageIds === []) {
            return;
        }

        $images = $product->images()->whereIn('id', $imageIds)->get();

        foreach ($images as $image) {
            $this->deleteImageFile($image);
            $image->delete();
        }
    }

    private function ensurePrimaryImage(Product $product): void
    {
        $product->load('images');

        if ($product->images->isEmpty() || $product->images->contains('is_primary', true)) {
            return;
        }

        $product->images->first()->update(['is_primary' => true]);
    }

    private function deleteImageFile(ProductImage $image): void
    {
        if (! str_starts_with($image->path, 'http')) {
            Storage::disk('public')->delete($image->path);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = "{$base}-".$suffix++;
        }

        return $slug;
    }
}
