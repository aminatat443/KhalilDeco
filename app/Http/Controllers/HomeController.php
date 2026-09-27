<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Page d'accueil — vitrine digitale (section 12 du cahier des charges).
     */
    public function index(): View
    {
        $universes = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $newProducts = $this->productsQuery()
            ->where('is_new', true)
            ->latest()
            ->take(8)
            ->get();

        $promoProducts = $this->productsQuery()
            ->where('is_promo', true)
            ->latest()
            ->take(8)
            ->get();

        // Sections éditoriales par univers (sections 15 à 18 du cahier des charges)
        $editorials = $universes->mapWithKeys(function (Category $universe) {
            $categoryIds = $universe->children()->pluck('id')->push($universe->id);

            return [
                $universe->slug => $this->productsQuery()
                    ->whereIn('category_id', $categoryIds)
                    ->latest()
                    ->take(4)
                    ->get(),
            ];
        });

        // Slider du hero : produits phares, en promo et nouveautés, avec photo (section 12)
        $heroSlides = $this->productsQuery()
            ->whereHas('images')
            ->orderByDesc('is_featured')
            ->orderByDesc('is_promo')
            ->orderByDesc('is_new')
            ->latest()
            ->take(6)
            ->get()
            ->map(fn (Product $product) => [
                // "fit" plutôt que "fill" : les photos produit sont presque toujours en portrait,
                // un recadrage en paysage 1200x700 coupait le produit. On garde l'image entière,
                // non recadrée et sans fond ajouté — c'est la vue (home/index.blade.php) qui
                // comble l'espace restant avec un arrière-plan flouté de cette même photo plutôt
                // que des bandes de couleur unie.
                'image' => img_url($product->images->first()->url, 1200, 700, 'fit'),
                'name' => $product->name,
                'price' => $product->price,
                'url' => route('products.show', $product),
            ]);

        return view('home.index', [
            'universes' => $universes,
            'newProducts' => $newProducts,
            'promoProducts' => $promoProducts,
            'editorials' => $editorials,
            'heroSlides' => $heroSlides,
        ]);
    }

    /**
     * Base commune des requêtes produit pour la homepage : images + compte de variantes
     * en eager loading pour éviter le N+1 dans <x-product-card> (section 56 du cahier des charges).
     */
    private function productsQuery()
    {
        return Product::where('is_active', true)
            ->with(['images' => fn ($q) => $q->orderBy('sort_order')])
            ->withCount('variants')
            ->withRatings();
    }
}
