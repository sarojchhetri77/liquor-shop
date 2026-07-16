<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Carbon\CarbonInterface;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $order_number
 * @property int $user_id
 * @property OrderStatus $status
 * @property string $payment_method
 * @property string $subtotal
 * @property string $total
 * @property string $customer_name
 * @property string $contact
 * @property string $shipping_address
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, OrderItem> $items
 * @property-read User $user
 * @property-read bool $can_cancel
 * @property-read CarbonInterface|null $cancellable_until
 */
#[Fillable([
    'order_number',
    'user_id',
    'status',
    'payment_method',
    'subtotal',
    'total',
    'customer_name',
    'contact',
    'shipping_address',
    'note',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * How long (in minutes) after placing an order a customer may cancel it.
     */
    public const CANCEL_WINDOW_MINUTES = 5;

    /**
     * @var list<string>
     */
    protected $appends = ['can_cancel', 'cancellable_until'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /**
     * The moment the customer's cancellation window closes.
     *
     * @return Attribute<CarbonInterface|null, never>
     */
    protected function cancellableUntil(): Attribute
    {
        return Attribute::get(
            fn (): ?CarbonInterface => $this->created_at?->copy()->addMinutes(self::CANCEL_WINDOW_MINUTES),
        );
    }

    /**
     * Whether the customer can still cancel this order: it must be pending
     * and within the cancellation window after being placed.
     *
     * @return Attribute<bool, never>
     */
    protected function canCancel(): Attribute
    {
        return Attribute::get(
            fn (): bool => $this->status === OrderStatus::Pending
                && $this->cancellable_until !== null
                && Carbon::now()->lte($this->cancellable_until),
        );
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
