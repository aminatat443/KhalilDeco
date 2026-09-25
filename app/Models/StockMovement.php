<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const TYPE_ENTREE = 'entree';
    public const TYPE_SORTIE = 'sortie';

    public const TYPE_LABELS = [
        self::TYPE_ENTREE => 'Entrée',
        self::TYPE_SORTIE => 'Sortie',
    ];

    protected $fillable = ['product_id', 'product_variant_id', 'user_id', 'type', 'quantity', 'reason'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
