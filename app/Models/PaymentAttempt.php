<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAttempt extends Model
{
    protected $fillable = [
        'reference',
        'customer_name',
        'customer_phone',
        'customer_email',
        'user_id',
        'payment_method',
        'subtotal',
        'discount',
        'amount',
        'cart_snapshot',
        'delivery_snapshot',
        'coupon_id',
        'status',
        'raw_response',
        'order_id',
    ];

    protected function casts(): array
    {
        return [
            'cart_snapshot' => 'array',
            'delivery_snapshot' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
