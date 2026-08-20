<?php

namespace App\Services;

use App\Models\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class BrandService
{
    /**
     * Paginate brands for the admin panel, optionally filtered by name.
     *
     * @return LengthAwarePaginator<int, Brand>
     */
    public function paginate(?string $search = null, int $perPage = 12): LengthAwarePaginator
    {
        return Brand::query()
            ->withCount('products')
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create a brand, or return the existing one when a brand with the same
     * name (case-insensitively) is already on file. Brands are added inline
     * from the product form, so duplicates are easy to submit by accident.
     *
     * @param  array{name: string}  $data
     */
    public function create(array $data): Brand
    {
        $name = trim($data['name']);

        $existing = Brand::whereRaw('LOWER(name) = ?', [Str::lower($name)])->first();

        if ($existing !== null) {
            return $existing;
        }

        return Brand::create([
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
        ]);
    }

    /**
     * @param  array{name: string}  $data
     */
    public function update(Brand $brand, array $data): Brand
    {
        $name = trim($data['name']);

        // Keep the slug in step with the name, but leave it alone on a no-op
        // rename so existing links do not churn.
        if ($name !== $brand->name) {
            $brand->slug = $this->uniqueSlug($name, $brand);
        }

        $brand->name = $name;
        $brand->save();

        return $brand;
    }

    /**
     * Delete a brand. Products keep their row and simply lose the brand, as
     * the foreign key is set to null on delete.
     */
    public function delete(Brand $brand): void
    {
        $brand->delete();
    }

    private function uniqueSlug(string $name, ?Brand $ignore = null): string
    {
        $base = Str::slug($name) ?: 'brand';
        $slug = $base;
        $suffix = 1;

        while (Brand::where('slug', $slug)->when($ignore, fn ($q) => $q->whereKeyNot($ignore->id))->exists()) {
            $slug = "{$base}-".$suffix++;
        }

        return $slug;
    }
}
