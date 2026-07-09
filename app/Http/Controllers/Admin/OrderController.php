<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();

        return Inertia::render('admin/orders/Index', [
            'orders' => Order::with('user')
                ->when($status, fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => ['status' => $status],
            'statuses' => OrderStatus::values(),
        ]);
    }

    public function show(Order $order): Response
    {
        return Inertia::render('admin/orders/Show', [
            'order' => $order->load(['items.product', 'user']),
            'statuses' => OrderStatus::values(),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(OrderStatus::values())],
        ]);

        $this->orders->updateStatus($order, OrderStatus::from($validated['status']));
        $this->toast('Order status updated.');

        return back();
    }
}
