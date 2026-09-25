<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'low_stock_notified_at',
        'promo_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'low_stock_notified_at' => 'datetime',
            'promo_notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Forme attendue par le store Alpine $store.favorites (header/product-card) : {id, name,
     * price, image, url} — utilisé à la fois pour hydrater la page d'un client connecté et pour
     * répondre après une synchronisation depuis le localStorage.
     *
     * @return array<int, array{id: int, name: string, price: int, image: ?string, url: string}>
     */
    public static function summaryFor(int $userId): array
    {
        return static::where('user_id', $userId)
            ->with(['product.images'])
            ->get()
            ->filter(fn (Favorite $favorite) => $favorite->product !== null)
            ->map(fn (Favorite $favorite) => [
                'id' => $favorite->product->id,
                'name' => $favorite->product->name,
                'price' => $favorite->product->price,
                'image' => $favorite->product->images->first()?->url,
                'url' => route('products.show', $favorite->product),
            ])
            ->values()
            ->all();
    }
}
