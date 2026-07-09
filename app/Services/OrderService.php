<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private CartService $cartService) {}

    /**
     * Place a Cash-on-Delivery order from the user's current cart.
     *
     * @param  array{customer_name: string, contact: string, shipping_address: string, note?: string|null}  $details
     */
    public function placeFromCart(User $user, array $details): Order
    {
        $items = $this->cartService->items($user);

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        return DB::transaction(function () use ($user, $items, $details): Order {
            $totals = $this->cartService->totals($items);

            $order = $user->orders()->create([
                'order_number' => $this->generateOrderNumber(),
                'status' => OrderStatus::Pending,
                'payment_method' => 'cod',
                'subtotal' => $totals['subtotal'],
                'total' => $totals['total'],
                'customer_name' => $details['customer_name'],
                'contact' => $details['contact'],
                'shipping_address' => $details['shipping_address'],
                'note' => $details['note'] ?? null,
            ]);

            foreach ($items as $item) {
                $product = $item->product;
                $unitPrice = $product->final_price;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $unitPrice,
                    'quantity' => $item->quantity,
                    'line_total' => round($unitPrice * $item->quantity, 2),
                ]);

                $product->decrement('stock', min($product->stock, $item->quantity));
            }

            $this->cartService->clear($user);

            return $order->load('items');
        });
    }

    public function updateStatus(Order $order, OrderStatus $status): Order
    {
        $order->update(['status' => $status]);

        return $order;
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-'.strtoupper(Str::random(10));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
