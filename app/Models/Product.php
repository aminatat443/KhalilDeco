<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /**
     * Nom donné au brouillon créé par Admin\ProductController::create() — utilisé aussi par
     * isPristineDraft() pour repérer un brouillon jamais retouché (voir sa docblock).
     */
    public const DRAFT_NAME = 'Nouveau produit';

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'specifications',
        'usage_instructions',
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

    /**
     * Photos génériques du produit (aucune variante) — la galerie affichée par défaut, avant
     * toute sélection, ou pour les variantes qui n'ont pas leurs propres photos. Les photos
     * propres à une variante exacte (ProductVariant::images()) en sont exclues : elles ne
     * doivent jamais apparaître comme vignette/carte générique du produit.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->whereNull('product_variant_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * "Nouveau produit" crée immédiatement un brouillon en base (voir Admin\ProductController
     * ::create()) pour réutiliser tout de suite le même écran d'édition que la modification
     * d'un produit existant — mais si l'admin ferme sans avoir rien renseigné, ce brouillon
     * resterait indéfiniment dans le catalogue (inactif, mais visible dans la liste). Un
     * brouillon est "vierge" tant qu'aucun champ, aucune photo et aucune variante n'a été
     * ajouté — dès qu'une seule de ces choses change, il devient un vrai produit à conserver.
     */
    public function isPristineDraft(): bool
    {
        return $this->name === self::DRAFT_NAME
            && $this->price === 0
            && blank($this->description)
            && blank($this->specifications)
            && blank($this->usage_instructions)
            && blank($this->model)
            && blank($this->material)
            && $this->images()->count() === 0
            && $this->variants()->count() === 0;
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
