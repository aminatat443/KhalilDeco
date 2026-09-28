<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductService;
use App\Services\PromotionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly PromotionService $promotions,
    ) {
    }

    /**
     * Fiche produit (sections 25 à 29 du cahier des charges).
     */
    public function show(Product $product): View
    {
        $product->load([
            'images' => fn ($q) => $q->orderBy('sort_order'),
            'variants.attributeValues.attribute',
            'variants.images' => fn ($q) => $q->orderBy('sort_order'),
            'category',
        ]);

        // Attributs à proposer sur la fiche produit (Couleur, Puissance...) — dérivés des
        // combinaisons réellement utilisées par les variantes de CE produit, pas de la liste
        // complète des attributs de la catégorie (qui peut en avoir plus que ce qui est vendu).
        $selectorAttributes = $product->variants
            ->flatMap(fn ($variant) => $variant->attributeValues)
            ->groupBy('attribute_id')
            ->map(function ($values) {
                $attribute = $values->first()->attribute;

                return [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'type' => $attribute->type,
                    'sortOrder' => $attribute->sort_order,
                    'values' => $values->unique('id')->sortBy('sort_order')->values()->map(fn ($v) => [
                        'id' => $v->id,
                        'value' => $v->value,
                        'colorCode' => $v->color_code,
                    ])->values(),
                ];
            })
            ->sortBy('sortOrder')
            ->values();

        $reviews = $product->reviews()->where('is_approved', true)->with('user')->latest()->get();
        $myReview = auth()->check() ? $product->reviews()->where('user_id', auth()->id())->first() : null;
        $canEditReview = $myReview && auth()->user()->can('update', $myReview);

        $relatedProducts = $this->relatedProducts($product);

        return view('products.show', [
            'product' => $product,
            'selectorAttributes' => $selectorAttributes,
            'effectivePrice' => $this->promotions->effectivePrice($product),
            'inStock' => $this->products->isInStock($product),
            'reviews' => $reviews,
            'averageRating' => $reviews->isNotEmpty() ? round($reviews->avg('rating'), 1) : null,
            'myReview' => $myReview,
            'canEditReview' => $canEditReview,
            'relatedProducts' => $relatedProducts,
        ]);
    }

    /**
     * Suggestions "Cela pourrait vous intéresser" : même sous-catégorie d'abord, puis élargi au
     * même univers si trop peu de résultats (le catalogue actuel a souvent un seul produit par
     * sous-catégorie), puis en dernier recours n'importe quel produit actif pour ne jamais
     * laisser la section vide.
     */
    private function relatedProducts(Product $product, int $limit = 12): Collection
    {
        $base = fn () => Product::where('is_active', true)
            ->where('id', '!=', $product->id)
            ->with(['images' => fn ($q) => $q->orderBy('sort_order')])
            ->withCount('variants')
            ->withRatings();

        $related = $base()->where('category_id', $product->category_id)->inRandomOrder()->take($limit)->get();

        if ($related->count() < $limit && $product->category?->parent_id) {
            $siblingCategoryIds = $product->category->parent->children()->pluck('id');

            $related = $related->concat(
                $base()->whereIn('category_id', $siblingCategoryIds)
                    ->whereNotIn('id', $related->pluck('id'))
                    ->inRandomOrder()
                    ->take($limit - $related->count())
                    ->get()
            );
        }

        if ($related->count() < $limit) {
            $related = $related->concat(
                $base()->whereNotIn('id', $related->pluck('id'))
                    ->inRandomOrder()
                    ->take($limit - $related->count())
                    ->get()
            );
        }

        return $related;
    }
}
