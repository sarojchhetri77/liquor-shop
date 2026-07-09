<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Get the user's cart items with product + primary image loaded.
     *
     * @return Collection<int, CartItem>
     */
    public function items(User $user): Collection
    {
        return $user->cartItems()
            ->with(['product.images', 'product.category'])
            ->get()
            ->filter(fn (CartItem $item): bool => $item->product !== null)
            ->values();
    }

    /**
     * Add a product to the cart (or increment its quantity).
     */
    public function add(User $user, Product $product, int $quantity = 1): CartItem
    {
        if (! $product->is_active) {
            throw ValidationException::withMessages([
                'product' => 'This product is not available.',
            ]);
        }

        $quantity = max(1, $quantity);

        $item = $user->cartItems()->firstOrNew(['product_id' => $product->id]);
        $item->quantity = ($item->exists ? $item->quantity : 0) + $quantity;
        $item->save();

        return $item;
    }

    /**
     * Set an explicit quantity for a cart item. Zero or less removes it.
     */
    public function updateQuantity(User $user, CartItem $item, int $quantity): void
    {
        $this->assertOwnership($user, $item);

        if ($quantity <= 0) {
            $item->delete();

            return;
        }

        $item->update(['quantity' => $quantity]);
    }

    public function remove(User $user, CartItem $item): void
    {
        $this->assertOwnership($user, $item);

        $item->delete();
    }

    public function clear(User $user): void
    {
        $user->cartItems()->delete();
    }

    public function count(User $user): int
    {
        return (int) $user->cartItems()->sum('quantity');
    }

    /**
     * Compute the cart totals using each product's discounted price.
     *
     * @param  Collection<int, CartItem>  $items
     * @return array{subtotal: float, total: float, count: int}
     */
    public function totals(Collection $items): array
    {
        $subtotal = $items->sum(
            fn (CartItem $item): float => $item->product->final_price * $item->quantity
        );

        return [
            'subtotal' => round($subtotal, 2),
            'total' => round($subtotal, 2),
            'count' => (int) $items->sum('quantity'),
        ];
    }

    private function assertOwnership(User $user, CartItem $item): void
    {
        if ($item->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'cart' => 'This cart item does not belong to you.',
            ]);
        }
    }
}
