<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index(Request $request): Response
    {
        $items = $this->cart->items($request->user());

        return Inertia::render('shop/Cart', [
            'items' => $items->map(fn (CartItem $item): array => [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'product' => [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'slug' => $item->product->slug,
                    'price' => $item->product->price,
                    'final_price' => $item->product->final_price,
                    'discount_percent' => $item->product->discount_percent,
                    'image' => $item->product->images->first()?->url,
                    'stock' => $item->product->stock,
                ],
            ]),
            'totals' => $this->cart->totals($items),
        ]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $quantity = max(1, (int) $request->integer('quantity', 1));

        $this->cart->add($request->user(), $product, $quantity);
        $this->toast("{$product->name} added to cart.");

        return back();
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        $this->cart->updateQuantity($request->user(), $cartItem, (int) $request->integer('quantity'));

        return back();
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        $this->cart->remove($request->user(), $cartItem);
        $this->toast('Item removed from cart.');

        return back();
    }
}
