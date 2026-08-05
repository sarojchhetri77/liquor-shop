<?php

namespace App\Http\Controllers\Shop;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The customer account dashboard: profile summary, order stats and the
     * most recent orders. Staff and admins are sent to the admin panel.
     */
    public function index(Request $request): RedirectResponse|Response
    {
        $user = $request->user();

        if ($user->canAccessAdminPanel()) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Dashboard', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'contact' => $user->contact,
                'dob' => $user->dob?->toDateString(),
                'member_since' => $user->created_at?->toDateString(),
            ],
            'stats' => [
                'orders' => $user->orders()->count(),
                'spent' => (float) $user->orders()
                    ->whereNot('status', OrderStatus::Cancelled->value)
                    ->sum('total'),
                'pending' => $user->orders()
                    ->where('status', OrderStatus::Pending->value)
                    ->count(),
            ],
            'recentOrders' => $user->orders()
                ->withCount('items')
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }
}
