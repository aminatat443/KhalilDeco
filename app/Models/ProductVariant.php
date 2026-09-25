<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'color_id',
        'size_id',
        'price',
        'stock',
        'sku',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    /**
     * Combinaison d'attributs génériques de la variante (ex. Couleur=Blanc + Puissance=12W) —
     * système utilisé par les nouveaux produits, en complément de color_id/size_id conservés
     * pour l'ancien catalogue mode (voir migration product_variant_attribute_value).
     */
    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variant_attribute_value')->with('attribute');
    }

    /**
     * Libellé lisible d'une variante, quel que soit le système utilisé (attributs génériques
     * en priorité, puis repli sur couleur/taille pour l'éventuel ancien catalogue) — null si la
     * variante n'a aucune caractéristique distinctive (ex. produit à variante unique).
     */
    public function labelOrNull(): ?string
    {
        $values = $this->relationLoaded('attributeValues') ? $this->attributeValues : $this->attributeValues()->get();

        if ($values->isNotEmpty()) {
            return $values->sortBy(fn ($v) => $v->attribute->sort_order)->pluck('value')->implode(' / ');
        }

        $legacy = collect([$this->color?->name, $this->size?->name])->filter()->implode(' / ');

        return $legacy !== '' ? $legacy : null;
    }

    public function label(): string
    {
        return $this->labelOrNull() ?? 'Standard';
    }
}
