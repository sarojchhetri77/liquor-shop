<?php

namespace App\Services;

use App\Models\Brand;
use Illuminate\Support\Str;

class BrandService
{
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

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'brand';
        $slug = $base;
        $suffix = 1;

        while (Brand::where('slug', $slug)->exists()) {
            $slug = "{$base}-".$suffix++;
        }

        return $slug;
    }
}
