<?php

namespace App\Services;

use App\Models\Promotion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PromotionService
{
    /**
     * @return LengthAwarePaginator<int, Promotion>
     */
    public function paginate(int $perPage = 12): LengthAwarePaginator
    {
        return Promotion::query()
            ->orderBy('sort_order')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * The active promotion to display in the storefront popup, if any.
     */
    public function activePopup(): ?Promotion
    {
        return Promotion::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->latest()
            ->first();
    }

    /**
     * @param  array{title: string, link?: string|null, is_active?: bool, sort_order?: int|null, image?: UploadedFile|null}  $data
     */
    public function create(array $data): Promotion
    {
        return Promotion::create([
            'title' => $data['title'],
            'link' => $data['link'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
            'image' => isset($data['image']) && $data['image'] instanceof UploadedFile
                ? $data['image']->store('promotions', 'public')
                : '',
        ]);
    }

    /**
     * @param  array{title: string, link?: string|null, is_active?: bool, sort_order?: int|null, image?: UploadedFile|null}  $data
     */
    public function update(Promotion $promotion, array $data): Promotion
    {
        $promotion->title = $data['title'];
        $promotion->link = $data['link'] ?? null;
        $promotion->is_active = $data['is_active'] ?? false;
        $promotion->sort_order = $data['sort_order'] ?? 0;

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $this->deleteImage($promotion);
            $promotion->image = $data['image']->store('promotions', 'public');
        }

        $promotion->save();

        return $promotion;
    }

    public function delete(Promotion $promotion): void
    {
        $this->deleteImage($promotion);
        $promotion->delete();
    }

    private function deleteImage(Promotion $promotion): void
    {
        if ($promotion->image && ! str_starts_with($promotion->image, 'http')) {
            Storage::disk('public')->delete($promotion->image);
        }
    }
}
