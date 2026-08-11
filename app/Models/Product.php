<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $category_id
 * @property int|null $brand_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $price
 * @property int $discount_percent
 * @property Carbon|null $discount_starts_at
 * @property Carbon|null $discount_ends_at
 * @property string $rating
 * @property int $reviews_count
 * @property int $stock
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Category $category
 * @property-read Brand|null $brand
 * @property-read Collection<int, ProductImage> $images
 * @property-read bool $is_discount_active
 * @property-read int $effective_discount_percent
 * @property-read float $final_price
 */
#[Fillable(['category_id', 'brand_id', 'name', 'slug', 'description', 'price', 'discount_percent', 'discount_starts_at', 'discount_ends_at', 'stock', 'is_active'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = ['is_discount_active', 'effective_discount_percent', 'final_price'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'rating' => 'decimal:2',
            'is_active' => 'boolean',
            'discount_starts_at' => 'datetime',
            'discount_ends_at' => 'datetime',
        ];
    }

    /**
     * Whether the product's discount is currently in effect. A discount is
     * active when a percentage is set and the current time falls within the
     * optional start/end window (a null bound means that side is open-ended).
     *
     * @return Attribute<bool, never>
     */
    protected function isDiscountActive(): Attribute
    {
        return Attribute::get(function (): bool {
            if ($this->discount_percent <= 0) {
                return false;
            }

            $now = Carbon::now();

            if ($this->discount_starts_at !== null && $now->lt($this->discount_starts_at)) {
                return false;
            }

            if ($this->discount_ends_at !== null && $now->gt($this->discount_ends_at)) {
                return false;
            }

            return true;
        });
    }

    /**
     * The discount percentage actually applied right now: the configured
     * percentage while the discount is active, otherwise zero.
     *
     * @return Attribute<int, never>
     */
    protected function effectiveDiscountPercent(): Attribute
    {
        return Attribute::get(
            fn (): int => $this->is_discount_active ? (int) $this->discount_percent : 0,
        );
    }

    /**
     * The price after applying the currently effective discount.
     *
     * @return Attribute<float, never>
     */
    protected function finalPrice(): Attribute
    {
        return Attribute::get(
            fn (): float => round((float) $this->price * (1 - $this->effective_discount_percent / 100), 2),
        );
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order');
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
