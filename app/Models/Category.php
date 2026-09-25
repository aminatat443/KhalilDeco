<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    /**
     * Attributs disponibles pour les produits de cette catégorie (section admin "Attributs
     * par catégorie") — ex. Couleur/Puissance/Température pour "Éclairage", Matière/Longueur
     * pour "Quincaillerie", plutôt que les mêmes champs figés pour tous les produits.
     */
    public function attributes(): BelongsToMany
    {
        // Nom de table explicite : Laravel attendrait "attribute_category" (ordre alphabétique)
        // par convention, mais la table a été créée sous le nom "category_attribute".
        return $this->belongsToMany(Attribute::class, 'category_attribute')->orderBy('sort_order');
    }
}
