<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
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
}
