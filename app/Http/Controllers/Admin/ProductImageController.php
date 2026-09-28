<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class ProductImageController extends Controller
{
    /**
     * Gestion des images produit (section 49 du cahier des charges), et — depuis l'ajout des
     * photos par variante — des images propres à une variante exacte (ex. "Blanc / 12W").
     *
     * Un seul contrôleur pour les deux : chaque route "produit" (products/{product}/images/...)
     * et son équivalent "variante" (products/{product}/variants/{variant}/images/...) pointent
     * vers les mêmes méthodes ci-dessous — Laravel ne peuple $variant que lorsque la route
     * appelée contient effectivement ce segment (voir routes/web.php), d'où le paramètre
     * optionnel systématique. Quand $variant est présent, les photos sont rattachées à
     * `product_variant_id` et n'apparaissent jamais dans la galerie générique du produit
     * (Product::images() les exclut explicitement) ni dans celle d'une autre variante.
     *
     * Bascule automatiquement sur Cloudinary dès que ses identifiants sont renseignés dans
     * .env (Setting::mediaDisk()), stockage local (disque `public`) en attendant.
     */
    public function store(Request $request, Product $product, ?ProductVariant $variant = null): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $product);
        $this->authorizeVariant($product, $variant);

        $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['image', 'max:4096'],
        ]);

        $disk = Setting::mediaDisk();
        $scope = $variant ?? $product;
        $nextOrder = $scope->images()->max('sort_order') + 1;

        foreach ($request->file('images') as $file) {
            $path = $file->store('products', $disk);

            $scope->images()->create([
                'product_id' => $product->id,
                'url' => Storage::disk($disk)->url($path),
                'public_id' => $disk === 'cloudinary' ? $path : null,
                'alt' => $product->name,
                'sort_order' => $nextOrder++,
            ]);
        }

        if ($request->wantsJson()) {
            return $this->gridResponse($product, $variant);
        }

        return back()->with('status', 'Image(s) ajoutée(s).');
    }

    /**
     * Photo principale : convention "plus petit sort_order" (déjà utilisée par tous les
     * `->first()` sur la relation images dans les contrôleurs vitrine), donc on fait passer
     * l'image choisie devant les autres sans introduire de colonne dédiée.
     */
    public function primary(Request $request, Product $product, ?ProductVariant $variant, ProductImage $image): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $product);
        $this->authorizeVariant($product, $variant);
        $this->authorizeImage($product, $variant, $image);

        $scope = $variant ?? $product;
        $scope->images()->where('id', '!=', $image->id)->increment('sort_order');
        $image->update(['sort_order' => 0]);

        if ($request->wantsJson()) {
            return $this->gridResponse($product, $variant);
        }

        return back()->with('status', 'Photo principale mise à jour.');
    }

    /**
     * Rendu de la grille d'images (partagé par store/primary/destroy) : permet aux appels AJAX
     * du back-office de mettre à jour l'affichage sans recharger la page ni déclencher de
     * bannière de statut en session.
     */
    private function gridResponse(Product $product, ?ProductVariant $variant): JsonResponse
    {
        $scope = $variant ?? $product;
        $scope->load('images');

        return response()->json([
            'html' => View::make('admin.products.partials.image-grid', [
                'product' => $product,
                'variant' => $variant,
                'images' => $scope->images,
            ])->render(),
        ]);
    }

    /**
     * Glisser-déposer pour réordonner les photos (section 49) — reçoit la liste des ids dans
     * le nouvel ordre et réécrit sort_order en conséquence.
     */
    public function reorder(Request $request, Product $product, ?ProductVariant $variant = null): JsonResponse
    {
        $this->authorize('update', $product);
        $this->authorizeVariant($product, $variant);

        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $scope = $variant ?? $product;
        $ids = collect($data['ids'])->intersect($scope->images()->pluck('id'))->values();

        foreach ($ids as $order => $id) {
            ProductImage::where('id', $id)->update(['sort_order' => $order]);
        }

        // La convention "principale = plus petit sort_order" fait déjà de l'image glissée en
        // première position la nouvelle principale côté serveur — renvoyer la grille à jour
        // permet au badge de suivre immédiatement, sans recharger la page.
        return $this->gridResponse($product, $variant);
    }

    public function destroy(Request $request, Product $product, ?ProductVariant $variant, ProductImage $image): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $product);
        $this->authorizeVariant($product, $variant);
        $this->authorizeImage($product, $variant, $image);

        if ($image->public_id) {
            Storage::disk('cloudinary')->delete($image->public_id);
        } elseif (str_contains($image->url, '/storage/products/')) {
            // Ne supprime le fichier local que s'il vient bien de notre stockage (pas une URL externe)
            Storage::disk('public')->delete('products/'.basename($image->url));
        }

        $image->delete();

        if ($request->wantsJson()) {
            return $this->gridResponse($product, $variant);
        }

        return back()->with('status', 'Image supprimée.');
    }

    /**
     * Une variante appartient-elle bien au produit de l'URL ? Évite qu'un id de variante d'un
     * autre produit, glissé dans l'URL, n'agisse sur ce produit-ci.
     */
    private function authorizeVariant(Product $product, ?ProductVariant $variant): void
    {
        if ($variant) {
            abort_unless($variant->product_id === $product->id, 404);
        }
    }

    /**
     * Une image appartient-elle bien au produit (et à la variante) de l'URL ?
     */
    private function authorizeImage(Product $product, ?ProductVariant $variant, ProductImage $image): void
    {
        abort_unless($image->product_id === $product->id, 404);
        abort_unless($image->product_variant_id === $variant?->id, 404);
    }
}
