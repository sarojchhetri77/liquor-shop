<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
            ->with(['category', 'brand', 'images'])
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
            ->with(['category', 'brand', 'images']);

        match ($filters['sort'] ?? null) {
            'price_low' => $query->orderBy('price'),
            'price_high' => $query->orderByDesc('price'),
            'rating' => $query->orderByDesc('rating'),
            default => $query->latest(),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Active products matching a search term, used by the storefront's
     * as-you-type search suggestions.
     *
     * @return Collection<int, Product>
     */
    public function suggest(string $search, int $limit = 6): Collection
    {
        return $this->filteredQuery(['search' => $search])
            ->active()
            ->with(['category', 'brand', 'images'])
            ->orderByDesc('rating')
            ->take($limit)
            ->get();
    }

    /**
     * Build a query filtered by product name / brand and category.
     *
     * @param  array{search?: string|null, category_id?: int|string|null}  $filters
     * @return Builder<Product>
     */
    private function filteredQuery(array $filters): Builder
    {
        return Product::query()
            ->when(
                $filters['search'] ?? null,
                fn (Builder $query, string $search) => $query->where(
                    fn (Builder $query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhereHas('brand', fn (Builder $brand) => $brand->where('name', 'like', "%{$search}%"))
                )
            )
            ->when(
                $filters['category_id'] ?? null,
                fn ($query, $categoryId) => $query->where('category_id', $categoryId)
            );
    }

    /**
     * @param  array{category_id: int, brand_id?: int|null, name: string, description?: string|null, price: numeric, discount_percent?: int|null, discount_starts_at?: string|null, discount_ends_at?: string|null, stock?: int|null, is_active?: bool, images?: array<int, UploadedFile>|null}  $data
     */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            $product = Product::create([
                'category_id' => $data['category_id'],
                'brand_id' => $data['brand_id'] ?? null,
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'discount_percent' => $data['discount_percent'] ?? 0,
                'discount_starts_at' => $data['discount_starts_at'] ?? null,
                'discount_ends_at' => $data['discount_ends_at'] ?? null,
                'stock' => $data['stock'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->syncNewImages($product, $data['images'] ?? []);

            return $product->load('images');
        });
    }

    /**
     * @param  array{category_id: int, brand_id?: int|null, name: string, description?: string|null, price: numeric, discount_percent?: int|null, discount_starts_at?: string|null, discount_ends_at?: string|null, stock?: int|null, is_active?: bool, images?: array<int, UploadedFile>|null, removed_image_ids?: array<int, int>|null}  $data
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $product->update([
                'category_id' => $data['category_id'],
                'brand_id' => $data['brand_id'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'discount_percent' => $data['discount_percent'] ?? 0,
                'discount_starts_at' => $data['discount_starts_at'] ?? null,
                'discount_ends_at' => $data['discount_ends_at'] ?? null,
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
     * Apply a discount percentage (and optional schedule) to a single product.
     */
    public function applyDiscount(Product $product, int $discountPercent, ?string $startsAt = null, ?string $endsAt = null): Product
    {
        $product->update([
            'discount_percent' => max(0, min(100, $discountPercent)),
            'discount_starts_at' => $startsAt,
            'discount_ends_at' => $endsAt,
        ]);

        return $product;
    }

    /**
     * Apply a discount (and optional schedule) to many products at once
     * (bulk discount tool).
     *
     * @param  array<int, int>  $productIds
     */
    public function applyBulkDiscount(array $productIds, int $discountPercent, ?string $startsAt = null, ?string $endsAt = null): int
    {
        return Product::whereIn('id', $productIds)
            ->update([
                'discount_percent' => max(0, min(100, $discountPercent)),
                'discount_starts_at' => $startsAt,
                'discount_ends_at' => $endsAt,
            ]);
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
