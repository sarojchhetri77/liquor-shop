<?php

namespace App\Http\Responses;

use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

/**
 * After a successful login or registration, add the product the guest was
 * trying to buy (stashed in the session as `pending_cart_product`) to their
 * cart and send them straight to the cart. Falls back to the default landing.
 */
class AuthRedirectResponse implements LoginResponseContract, RegisterResponseContract
{
    public function __construct(private CartService $cart) {}

    public function toResponse($request): RedirectResponse
    {
        $productId = $request->session()->pull('pending_cart_product');

        if ($productId !== null) {
            $product = Product::active()->find($productId);

            if ($product !== null) {
                $this->cart->add($request->user(), $product);
                Inertia::flash('toast', [
                    'type' => 'success',
                    'message' => "{$product->name} added to cart.",
                ]);

                return redirect()->route('shop.cart.index');
            }
        }

        return redirect()->intended(config('fortify.home'));
    }
}
