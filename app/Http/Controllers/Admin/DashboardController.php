<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $paidOrders = Order::whereNot('status', OrderStatus::Cancelled->value);
        $revenue = (float) (clone $paidOrders)->sum('total');
        $ordersCount = (clone $paidOrders)->count();

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'products' => Product::count(),
                'orders' => Order::count(),
                'pendingOrders' => Order::where('status', OrderStatus::Pending->value)->count(),
                'customers' => User::where('role', UserRole::Customer->value)->count(),
                'revenue' => $revenue,
                'avgOrderValue' => $ordersCount > 0 ? round($revenue / $ordersCount, 2) : 0,
            ],
            'revenueSeries' => $this->revenueSeries(),
            'statusBreakdown' => $this->statusBreakdown(),
            'topCategories' => $this->topCategories(),
            'recentOrders' => Order::with('user')
                ->latest()
                ->take(6)
                ->get()
                ->map(fn (Order $order): array => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'total' => $order->total,
                    'status' => $order->status->value,
                    'created_at' => $order->created_at?->diffForHumans(),
                ]),
        ]);
    }

    /**
     * Daily revenue and order counts for the last 14 days.
     *
     * @return array<int, array{label: string, short: string, revenue: float, orders: int}>
     */
    private function revenueSeries(): array
    {
        $since = now()->subDays(13)->startOfDay();

        $daily = Order::query()
            ->where('created_at', '>=', $since)
            ->whereNot('status', OrderStatus::Cancelled->value)
            ->selectRaw('date(created_at) as day, sum(total) as revenue, count(*) as orders')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $series = [];

        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $row = $daily->get($date->format('Y-m-d'));

            $series[] = [
                'label' => $date->format('M j'),
                'short' => $date->format('j'),
                'revenue' => (float) ($row->revenue ?? 0),
                'orders' => (int) ($row->orders ?? 0),
            ];
        }

        return $series;
    }

    /**
     * Order counts grouped by status (all statuses, including zero).
     *
     * @return array<int, array{status: string, count: int}>
     */
    private function statusBreakdown(): array
    {
        $counts = Order::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return array_map(fn (OrderStatus $status): array => [
            'status' => $status->value,
            'count' => (int) ($counts[$status->value] ?? 0),
        ], OrderStatus::cases());
    }

    /**
     * Best-selling categories by revenue.
     *
     * @return array<int, array{name: string, revenue: float, qty: int}>
     */
    private function topCategories(): array
    {
        return DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as name, sum(order_items.line_total) as revenue, sum(order_items.quantity) as qty')
            ->groupBy('categories.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => [
                'name' => $row->name,
                'revenue' => (float) $row->revenue,
                'qty' => (int) $row->qty,
            ])
            ->all();
    }
}
