<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartService
{
    private const SESSION_KEY = 'cart';

    public function __construct(private readonly PromotionService $promotions)
    {
    }

    public function add(Product $product, ?ProductVariant $variant, int $quantity = 1): void
    {
        $key = $this->itemKey($product->id, $variant?->id);
        $cart = $this->raw();
        $cart[$key] = ($cart[$key] ?? 0) + $quantity;
        Session::put(self::SESSION_KEY, $cart);
        $this->syncPersisted($cart);
    }

    public function updateQuantity(int $productId, ?int $variantId, int $quantity): void
    {
        $key = $this->itemKey($productId, $variantId);
        $cart = $this->raw();

        if ($quantity <= 0) {
            unset($cart[$key]);
        } else {
            $cart[$key] = $quantity;
        }

        Session::put(self::SESSION_KEY, $cart);
        $this->syncPersisted($cart);
    }

    public function remove(int $productId, ?int $variantId): void
    {
        $this->updateQuantity($productId, $variantId, 0);
    }

    /**
     * Affecte une variante (taille/couleur) à une ligne du panier ajoutée sans variante —
     * le client choisit au moment de la commande plutôt qu'à l'ajout au panier.
     */
    public function assignVariant(int $productId, int $variantId): void
    {
        $cart = $this->raw();
        $oldKey = $this->itemKey($productId, null);
        $quantity = $cart[$oldKey] ?? 1;
        unset($cart[$oldKey]);

        $newKey = $this->itemKey($productId, $variantId);
        $cart[$newKey] = ($cart[$newKey] ?? 0) + $quantity;

        Session::put(self::SESSION_KEY, $cart);
        $this->syncPersisted($cart);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);

        if (Auth::check()) {
            Cart::where('user_id', Auth::id())->delete();
        }
    }

    /**
     * Copie le panier en base pour le client connecté — seule façon de détecter un panier
     * abandonné depuis une commande planifiée (la session expire après {@see config('session.lifetime')}
     * minutes, bien avant le délai de relance). Un panier vidé supprime la ligne plutôt que de la
     * garder vide ; toute modification réinitialise `reminded_at` (nouvel épisode d'abandon).
     */
    private function syncPersisted(array $cart): void
    {
        if (! Auth::check()) {
            return;
        }

        if (empty($cart)) {
            Cart::where('user_id', Auth::id())->delete();

            return;
        }

        $items = collect($cart)->map(function (int $quantity, string $key) {
            [$productId, $variantId] = $this->parseKey($key);

            return ['product_id' => $productId, 'variant_id' => $variantId, 'quantity' => $quantity];
        })->values()->all();

        Cart::updateOrCreate(
            ['user_id' => Auth::id()],
            ['items' => $items, 'reminded_at' => null]
        );
    }

    /**
     * Hydrate le panier en objets exploitables (produit, variante, quantité, prix, sous-total).
     * Le prix est toujours recalculé depuis la base — jamais fait confiance à une valeur envoyée
     * par le client (cohérent avec la section 58 du cahier des charges — validation serveur).
     *
     * @return list<array{product: Product, variant: ?ProductVariant, quantity: int, unit_price: int, subtotal: int}>
     */
    public function items(): array
    {
        $items = [];

        foreach ($this->raw() as $key => $quantity) {
            [$productId, $variantId] = $this->parseKey($key);

            $product = Product::find($productId);
            if (! $product) {
                continue; // produit supprimé depuis l'ajout au panier
            }

            $variant = $variantId ? ProductVariant::with('attributeValues.attribute')->find($variantId) : null;
            $unitPrice = $this->promotions->effectivePrice($product, $variant);

            $items[] = [
                'product' => $product,
                'variant' => $variant,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $unitPrice * $quantity,
            ];
        }

        return $items;
    }

    public function subtotal(): int
    {
        return array_sum(array_column($this->items(), 'subtotal'));
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    /**
     * Représentation JSON-friendly du panier, pour le tiroir flottant mis à jour sans
     * rechargement de page (fetch + Alpine store côté client).
     */
    public function summary(): array
    {
        $items = $this->items();

        return [
            'items' => array_map(function (array $item) {
                $needsVariant = is_null($item['variant']) && $item['product']->variants()->exists();

                return [
                    'product_id' => $item['product']->id,
                    'variant_id' => $item['variant']?->id,
                    'needs_variant' => $needsVariant,
                    'variant_options' => $needsVariant ? $this->variantOptions($item['product']) : null,
                    'name' => $item['product']->name,
                    'image' => $item['product']->images->first()?->url,
                    'variant_label' => $item['variant']?->labelOrNull(),
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'url' => route('products.show', $item['product']),
                ];
            }, $items),
            'count' => array_sum(array_column($items, 'quantity')),
            'subtotal' => array_sum(array_column($items, 'subtotal')),
        ];
    }

    /**
     * Attributs (couleur, puissance...) et grille des variantes d'un produit, pour permettre au
     * client de choisir directement dans le panier (docs/SPEC.md — pas de sélection forcée à
     * l'ajout). Dérivés des combinaisons réellement utilisées par les variantes du produit.
     */
    private function variantOptions(Product $product): array
    {
        $variants = $product->variants()->with('attributeValues.attribute')->get();

        $attributes = $variants
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

        return [
            'attributes' => $attributes,
            'variants' => $variants
                ->map(fn ($variant) => [
                    'id' => $variant->id,
                    'stock' => $variant->stock,
                    'values' => $variant->attributeValues->pluck('id')->values(),
                ])
                ->all(),
        ];
    }

    private function raw(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    private function itemKey(int $productId, ?int $variantId): string
    {
        return $productId.':'.($variantId ?? '0');
    }

    private function parseKey(string $key): array
    {
        [$productId, $variantId] = explode(':', $key);

        return [(int) $productId, $variantId === '0' ? null : (int) $variantId];
    }
}
