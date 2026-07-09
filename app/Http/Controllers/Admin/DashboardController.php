<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * @var array<string, array{label: string, unit: string, buckets: int}>
     */
    private array $rangeConfig = [
        '7d' => ['label' => 'Last 7 days', 'unit' => 'day', 'buckets' => 7],
        '14d' => ['label' => 'Last 14 days', 'unit' => 'day', 'buckets' => 14],
        '30d' => ['label' => 'Last 30 days', 'unit' => 'day', 'buckets' => 30],
        '1y' => ['label' => 'Last 12 months', 'unit' => 'month', 'buckets' => 12],
        'all' => ['label' => 'All time', 'unit' => 'month', 'buckets' => 0],
    ];

    public function index(Request $request): Response
    {
        $range = $request->string('range')->toString();
        if (! isset($this->rangeConfig[$range])) {
            $range = '14d';
        }

        $config = $this->rangeConfig[$range];
        $since = $this->rangeStart($range);

        $scoped = Order::query()->whereNot('status', OrderStatus::Cancelled->value);
        if ($since !== null) {
            $scoped->where('created_at', '>=', $since);
        }

        $revenue = (float) (clone $scoped)->sum('total');
        $ordersInRange = (clone $scoped)->count();

        return Inertia::render('admin/Dashboard', [
            'range' => $range,
            'ranges' => [
                ['value' => '7d', 'label' => '7 days'],
                ['value' => '14d', 'label' => '14 days'],
                ['value' => '30d', 'label' => '1 month'],
                ['value' => '1y', 'label' => '1 year'],
                ['value' => 'all', 'label' => 'All'],
            ],
            'chartLabel' => $config['label'],
            'stats' => [
                'products' => Product::count(),
                'orders' => $ordersInRange,
                'pendingOrders' => Order::where('status', OrderStatus::Pending->value)->count(),
                'customers' => User::where('role', UserRole::Customer->value)->count(),
                'revenue' => $revenue,
                'avgOrderValue' => $ordersInRange > 0 ? round($revenue / $ordersInRange, 2) : 0,
            ],
            'revenueSeries' => $this->revenueSeries($config['unit'], $since, $config['buckets']),
            'statusBreakdown' => $this->statusBreakdown($since),
            'topCategories' => $this->topCategories($since),
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

    private function rangeStart(string $range): ?CarbonInterface
    {
        return match ($range) {
            '7d' => now()->subDays(6)->startOfDay(),
            '14d' => now()->subDays(13)->startOfDay(),
            '30d' => now()->subDays(29)->startOfDay(),
            '1y' => now()->subMonths(11)->startOfMonth(),
            default => null, // all time
        };
    }

    /**
     * Revenue and order counts bucketed by day or month.
     *
     * @return array<int, array{label: string, short: string, revenue: float, orders: int}>
     */
    private function revenueSeries(string $unit, ?CarbonInterface $since, int $buckets): array
    {
        $periods = [];

        if ($unit === 'day') {
            for ($i = $buckets - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $periods[] = ['key' => $date->format('Y-m-d'), 'label' => $date->format('M j')];
            }
            $groupExpr = "strftime('%Y-%m-%d', created_at)";
            $from = now()->subDays($buckets - 1)->startOfDay();
        } else {
            $start = $since ?? ($this->earliestOrderDate()?->startOfMonth() ?? now()->startOfMonth());
            $cursor = $start->copy()->startOfMonth();
            $end = now()->startOfMonth();

            while ($cursor <= $end) {
                $periods[] = ['key' => $cursor->format('Y-m'), 'label' => $cursor->format('M Y')];
                $cursor = $cursor->addMonth();
            }
            $groupExpr = "strftime('%Y-%m', created_at)";
            $from = $start;
        }

        $rows = Order::query()
            ->where('created_at', '>=', $from)
            ->whereNot('status', OrderStatus::Cancelled->value)
            ->selectRaw("{$groupExpr} as bucket, sum(total) as revenue, count(*) as orders")
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');

        return array_map(fn (array $period): array => [
            'label' => $period['label'],
            'short' => $period['label'],
            'revenue' => (float) ($rows[$period['key']]->revenue ?? 0),
            'orders' => (int) ($rows[$period['key']]->orders ?? 0),
        ], $periods);
    }

    private function earliestOrderDate(): ?CarbonInterface
    {
        $first = Order::min('created_at');

        return $first ? Carbon::parse($first) : null;
    }

    /**
     * @return array<int, array{status: string, count: int}>
     */
    private function statusBreakdown(?CarbonInterface $since): array
    {
        $counts = Order::query()
            ->when($since, fn ($query) => $query->where('created_at', '>=', $since))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return array_map(fn (OrderStatus $status): array => [
            'status' => $status->value,
            'count' => (int) ($counts[$status->value] ?? 0),
        ], OrderStatus::cases());
    }

    /**
     * @return array<int, array{name: string, revenue: float, qty: int}>
     */
    private function topCategories(?CarbonInterface $since): array
    {
        return DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->when($since, fn ($query) => $query->where('orders.created_at', '>=', $since))
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
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
