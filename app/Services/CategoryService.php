<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryService
{
    /**
     * Paginate categories for the admin panel, optionally filtered by name.
     *
     * @return LengthAwarePaginator<int, Category>
     */
    public function paginate(?string $search = null, int $perPage = 12): LengthAwarePaginator
    {
        return Category::query()
            ->withCount('products')
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array{name: string, description?: string|null, image?: UploadedFile|null}  $data
     */
    public function create(array $data): Category
    {
        return Category::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'image' => isset($data['image']) && $data['image'] instanceof UploadedFile
                ? $this->storeImage($data['image'])
                : null,
        ]);
    }

    /**
     * @param  array{name: string, description?: string|null, image?: UploadedFile|null}  $data
     */
    public function update(Category $category, array $data): Category
    {
        $category->name = $data['name'];
        $category->description = $data['description'] ?? null;

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $this->deleteImage($category);
            $category->image = $this->storeImage($data['image']);
        }

        $category->save();

        return $category;
    }

    public function delete(Category $category): void
    {
        $this->deleteImage($category);
        $category->delete();
    }

    private function storeImage(UploadedFile $file): string
    {
        return $file->store('categories', 'public');
    }

    private function deleteImage(Category $category): void
    {
        if ($category->image && ! str_starts_with($category->image, 'http')) {
            Storage::disk('public')->delete($category->image);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$base}-".$suffix++;
        }

        return $slug;
    }
}
