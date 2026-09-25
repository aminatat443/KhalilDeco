<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProformaInvoice extends Model
{
    protected $fillable = [
        'reference',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_address',
        'notes',
        'created_by',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ProformaInvoiceItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function total(): int
    {
        return (int) $this->items->sum('subtotal');
    }
}
