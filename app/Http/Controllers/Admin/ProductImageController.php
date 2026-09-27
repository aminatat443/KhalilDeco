<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class ProductImageController extends Controller
{
    /**
     * Gestion des images produit (section 49 du cahier des charges).
     *
     * Bascule automatiquement sur Cloudinary dès que ses identifiants sont renseignés dans
     * .env (Setting::mediaDisk()), stockage local (disque `public`) en attendant.
     */
    public function store(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $product);

        $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['image', 'max:4096'],
        ]);

        $disk = Setting::mediaDisk();
        $nextOrder = $product->images()->max('sort_order') + 1;

        foreach ($request->file('images') as $file) {
            $path = $file->store('products', $disk);

            $product->images()->create([
                'url' => Storage::disk($disk)->url($path),
                'public_id' => $disk === 'cloudinary' ? $path : null,
                'alt' => $product->name,
                'sort_order' => $nextOrder++,
            ]);
        }

        if ($request->wantsJson()) {
            return $this->gridResponse($product);
        }

        return back()->with('status', 'Image(s) ajoutée(s).');
    }

    /**
     * Photo principale : convention "plus petit sort_order" (déjà utilisée par tous les
     * `->first()` sur la relation images dans les contrôleurs vitrine), donc on fait passer
     * l'image choisie devant les autres sans introduire de colonne dédiée.
     */
    public function primary(Request $request, Product $product, ProductImage $image): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $product);

        abort_unless($image->product_id === $product->id, 404);

        $product->images()->where('id', '!=', $image->id)->increment('sort_order');
        $image->update(['sort_order' => 0]);

        if ($request->wantsJson()) {
            return $this->gridResponse($product);
        }

        return back()->with('status', 'Photo principale mise à jour.');
    }

    /**
     * Rendu de la grille d'images (partagé par store/primary/destroy) : permet aux appels AJAX
     * du back-office de mettre à jour l'affichage sans recharger la page ni déclencher de
     * bannière de statut en session.
     */
    private function gridResponse(Product $product): JsonResponse
    {
        $product->load('images');

        return response()->json([
            'html' => View::make('admin.products.partials.image-grid', ['product' => $product])->render(),
        ]);
    }

    /**
     * Glisser-déposer pour réordonner les photos (section 49) — reçoit la liste des ids dans
     * le nouvel ordre et réécrit sort_order en conséquence.
     */
    public function reorder(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $ids = collect($data['ids'])->intersect($product->images()->pluck('id'))->values();

        foreach ($ids as $order => $id) {
            ProductImage::where('id', $id)->update(['sort_order' => $order]);
        }

        // La convention "principale = plus petit sort_order" fait déjà de l'image glissée en
        // première position la nouvelle principale côté serveur — renvoyer la grille à jour
        // permet au badge de suivre immédiatement, sans recharger la page.
        return $this->gridResponse($product);
    }

    public function destroy(Request $request, Product $product, ProductImage $image): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $product);

        if ($image->public_id) {
            Storage::disk('cloudinary')->delete($image->public_id);
        } elseif (str_contains($image->url, '/storage/products/')) {
            // Ne supprime le fichier local que s'il vient bien de notre stockage (pas une URL externe)
            Storage::disk('public')->delete('products/'.basename($image->url));
        }

        $image->delete();

        if ($request->wantsJson()) {
            return $this->gridResponse($product);
        }

        return back()->with('status', 'Image supprimée.');
    }
}
