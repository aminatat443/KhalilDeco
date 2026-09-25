<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'model',
        'price',
        'old_price',
        'cost_price',
        'material',
        'stock',
        'is_new',
        'is_promo',
        'is_featured',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_new' => 'boolean',
            'is_promo' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Note moyenne + nombre d'avis approuvés, en une seule requête agrégée par lot — pour
     * afficher les étoiles sur les cartes produit (listes) sans N+1 requête par carte.
     */
    public function scopeWithRatings(Builder $query): Builder
    {
        return $query
            ->withCount(['reviews as reviews_count' => fn ($q) => $q->where('is_approved', true)])
            ->withAvg(['reviews as reviews_avg_rating' => fn ($q) => $q->where('is_approved', true)], 'rating');
    }

    /**
     * Note affichée sur les cartes et la fiche produit : la vraie moyenne si des avis existent,
     * sinon une note générique dérivée de l'id (stable d'un chargement à l'autre) — décision
     * assumée du commerçant, jamais un faux avis nommé (voir docs sur la fiche produit).
     */
    public function displayRating(?float $realAverage = null): float
    {
        return $realAverage ?? [4, 4.5, 5][$this->id % 3];
    }

    public function displayReviewsCount(int $realCount = 0): int
    {
        return $realCount > 0 ? $realCount : 3 + ($this->id * 7) % 24;
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
