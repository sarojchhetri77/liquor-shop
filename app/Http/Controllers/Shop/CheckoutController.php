<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\CheckoutRequest;
use App\Models\CartItem;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cart,
        private OrderService $orders,
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $items = $this->cart->items($user);

        if ($items->isEmpty()) {
            $this->toast('Your cart is empty.', 'error');

            return to_route('shop.cart.index');
        }

        return Inertia::render('shop/Checkout', [
            'items' => $items->map(fn (CartItem $item): array => [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'name' => $item->product->name,
                'final_price' => $item->product->final_price,
                'image' => $item->product->images->first()?->url,
            ]),
            'totals' => $this->cart->totals($items),
            'customer' => [
                'customer_name' => $user->name,
                'contact' => $user->contact,
            ],
        ]);
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        $order = $this->orders->placeFromCart($request->user(), $request->validated());
        $this->toast('Order placed successfully! Pay on delivery.');

        return to_route('shop.orders.show', $order);
    }
}
