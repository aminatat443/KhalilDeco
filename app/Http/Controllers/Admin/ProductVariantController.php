<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductVariantController extends Controller
{
    /**
     * Génération en masse des variantes (sections 27, 41, 45 du cahier des charges) : l'admin
     * coche plusieurs couleurs et/ou tailles, une case de stock apparaît pour chaque
     * combinaison — plus besoin de répéter "choisir couleur + taille + SKU + Ajouter" une
     * par une, et le stock de départ peut différer d'une combinaison à l'autre.
     */
    /**
     * Génération en masse des variantes à partir des attributs génériques de la catégorie du
     * produit (Couleur, Puissance, Longueur...) — l'admin coche une ou plusieurs valeurs par
     * attribut, une case de stock apparaît pour chaque combinaison. Remplace l'ancien système
     * couleur/taille figé pour tout produit dont la catégorie a des attributs assignés (voir
     * CategoryController et la section admin "Attributs").
     */
    public function store(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $product);

        $data = $request->validate([
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.attribute_value_ids' => ['nullable', 'array'],
            'variants.*.attribute_value_ids.*' => ['integer', 'exists:attribute_values,id'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
        ]);

        $valueLabels = AttributeValue::pluck('value', 'id');

        $existingCombos = $product->variants()->with('attributeValues')->get()
            ->map(fn ($variant) => $variant->attributeValues->pluck('id')->sort()->values()->all())
            ->all();

        $created = [];
        $skipped = 0;

        foreach ($data['variants'] as $row) {
            $valueIds = collect($row['attribute_value_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            if (in_array($valueIds, $existingCombos, true)) {
                $skipped++;

                continue;
            }

            $skuParts = [Str::upper($product->slug)];
            foreach ($valueIds as $valueId) {
                $skuParts[] = Str::upper(str_replace(' ', '', $valueLabels[$valueId] ?? ''));
            }

            $sku = $baseSku = implode('-', $skuParts);
            $suffix = 1;
            while (ProductVariant::where('sku', $sku)->exists()) {
                $sku = $baseSku.'-'.(++$suffix);
            }

            $variant = $product->variants()->create([
                'stock' => $row['stock'],
                'sku' => $sku,
            ]);
            $variant->attributeValues()->sync($valueIds);

            $existingCombos[] = $valueIds;
            $created[] = $variant;
        }

        $message = count($created) > 0 ? count($created).' variante(s) créée(s).' : 'Aucune nouvelle variante — toutes ces combinaisons existent déjà.';
        if ($skipped > 0) {
            $message .= " {$skipped} déjà existante(s) ignorée(s).";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'skipped' => $skipped,
                'variants' => collect($created)->map(fn (ProductVariant $v) => [
                    'id' => $v->id,
                    'label' => $v->label(),
                    'sku' => $v->sku,
                    'stock' => $v->stock,
                ])->values(),
            ]);
        }

        return back()->with('status', $message);
    }

    public function update(Request $request, Product $product, ProductVariant $variant): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $product);

        $data = $request->validate([
            'color_id' => ['nullable', 'integer', 'exists:colors,id'],
            'size_id' => ['nullable', 'integer', 'exists:sizes,id'],
            'price' => ['nullable', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'sku' => ['required', 'string', 'max:255', 'unique:product_variants,sku,'.$variant->id],
        ]);

        $variant->update($data);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Variante mise à jour.',
                'variant' => [
                    'id' => $variant->id,
                    'label' => $variant->label(),
                    'sku' => $variant->sku,
                    'stock' => $variant->stock,
                ],
            ]);
        }

        return back()->with('status', 'Variante mise à jour.');
    }

    public function destroy(Request $request, Product $product, ProductVariant $variant): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $product);

        $variant->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Variante supprimée.']);
        }

        return back()->with('status', 'Variante supprimée.');
    }
}
