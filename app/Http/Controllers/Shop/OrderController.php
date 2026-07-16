<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $request): Response
    {
        return Inertia::render('shop/Orders', [
            'orders' => $request->user()->orders()
                ->withCount('items')
                ->latest()
                ->paginate(10),
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return Inertia::render('shop/OrderDetail', [
            'order' => $order->load('items.product'),
        ]);
    }

    /**
     * Cancel a pending order within the allowed cancellation window.
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if (! $order->can_cancel) {
            $this->toast(
                sprintf('This order can no longer be cancelled — orders can only be cancelled within %d minutes of being placed.', Order::CANCEL_WINDOW_MINUTES),
                'error',
            );

            return back();
        }

        $this->orders->cancelForCustomer($order);
        $this->toast('Your order has been cancelled.');

        return back();
    }
}
