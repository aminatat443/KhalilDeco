<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

/**
 * Point unique de détection des produits "campagne automatique" — Nouveautés et Promotions ne
 * doivent jamais avoir leur propre base de produits (section 12 du cahier des charges) : ce
 * service lit uniquement les statuts déjà présents sur `products` (is_new / is_promo).
 */
class CampaignCatalogResolver
{
    /**
     * @return Collection<int, Product>
     */
    public function newArrivalsProducts(): Collection
    {
        return Product::where('is_new', true)
            ->where('is_active', true)
            ->with(['images', 'category'])
            ->latest()
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function activePromotionProducts(): Collection
    {
        return Product::where('is_promo', true)
            ->where('is_active', true)
            ->whereNotNull('old_price')
            ->whereColumn('old_price', '>', 'price')
            ->with(['images', 'category'])
            ->latest()
            ->get();
    }
}
