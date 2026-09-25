<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Size;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Gestion des produits (section 41 du cahier des charges).
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $threshold = Setting::current()->low_stock_threshold ?? 5;

        // "Rupture"/"stock faible" sont comptés côté tableau de bord au niveau de la variante
        // (un produit avec 2 variantes en rupture compte pour 2) alors que cette page liste des
        // PRODUITS — un produit ayant au moins une variante concernée est donc inclus ici, même si
        // le total affiché sur la carte (nombre de variantes) ne correspondra pas au nombre de
        // lignes de cette liste (nombre de produits). Granularité différente, mais c'est la
        // reproduction la plus fidèle possible sans changer la définition métier de la carte.
        $products = Product::query()
            ->with(['category', 'images' => fn ($q) => $q->orderBy('sort_order')])
            ->withCount('variants')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'ilike', '%'.$request->input('q').'%'))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->input('category_id')))
            ->when($request->input('stock') === 'out', fn ($q) => $q->where(fn ($q2) => $q2
                ->where(fn ($q3) => $q3->whereDoesntHave('variants')->where('stock', 0))
                ->orWhereHas('variants', fn ($v) => $v->where('stock', 0))))
            ->when($request->input('stock') === 'low', fn ($q) => $q->where(fn ($q2) => $q2
                ->where(fn ($q3) => $q3->whereDoesntHave('variants')->where('stock', '>', 0)->where('stock', '<=', $threshold))
                ->orWhereHas('variants', fn ($v) => $v->where('stock', '>', 0)->where('stock', '<=', $threshold))))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.products.partials.table', ['products' => $products])->render(),
            ]);
        }

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    /**
     * "Nouveau produit" crée immédiatement un brouillon (inactif, invisible en boutique) et
     * redirige vers l'édition — pour que l'admin retrouve tout de suite le même écran que la
     * modification d'un produit existant (photos avec glisser-déposer, variantes…) plutôt
     * qu'un formulaire minimal en attendant un premier enregistrement.
     */
    public function create(): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $category = Category::whereNotNull('parent_id')->orderBy('name')->first();

        abort_if(! $category, 500, 'Créez d\'abord une catégorie avant d\'ajouter un produit.');

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Nouveau produit',
            'slug' => $this->uniqueSlug('Nouveau produit'),
            'price' => 0,
            'is_active' => false,
        ]);

        return redirect()->route('admin.products.edit', $product);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $request->validate([
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:4096'],
        ]);

        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $product = Product::create($data);

        if ($request->hasFile('images')) {
            $disk = Setting::mediaDisk();
            $sortOrder = 0;

            foreach ($request->file('images') as $file) {
                $path = $file->store('products', $disk);

                $product->images()->create([
                    'url' => Storage::disk($disk)->url($path),
                    'public_id' => $disk === 'cloudinary' ? $path : null,
                    'alt' => $product->name,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        return redirect()->route('admin.products.edit', $product)->with('status', 'Produit créé.');
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        $product->load([
            'category.parent',
            'category.attributes.values',
            'images' => fn ($q) => $q->orderBy('sort_order'),
            'variants.color',
            'variants.size',
            'variants.attributeValues.attribute',
        ]);

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::whereNotNull('parent_id')->with('parent')->orderBy('name')->get(),
            'colors' => Color::orderBy('name')->get(),
            'sizes' => $this->sizesForCategory($product->category),
        ]);
    }

    /**
     * Les tailles n'ont de sens que pour les vêtements (S à XXL) et les chaussures
     * (pointures 36 à 46) — les autres univers (accessoires, maison & décoration) n'affichent
     * aucune taille plutôt qu'une liste sans rapport avec le produit.
     */
    private function sizesForCategory(?Category $category): \Illuminate\Support\Collection
    {
        $universeSlug = ($category?->parent ?? $category)?->slug;

        if (! in_array($universeSlug, ['femme', 'homme', 'chaussures'], true)) {
            return collect();
        }

        $sizes = Size::all();

        if ($universeSlug === 'chaussures') {
            return $sizes->filter(fn ($size) => is_numeric($size->name))
                ->sortBy(fn ($size) => (int) $size->name)
                ->values();
        }

        $order = ['S', 'M', 'L', 'XL', 'XXL'];

        return $sizes->filter(fn ($size) => in_array($size->name, $order, true))
            ->sortBy(fn ($size) => array_search($size->name, $order, true))
            ->values();
    }

    public function update(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $product);

        $data = $this->validated($request, $product->id);

        if ($data['name'] !== $product->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $product->id);
        }

        $product->update($data);

        if ($request->ajax()) {
            return response()->json(['status' => 'ok']);
        }

        return redirect()->route('admin.products.edit', $product)->with('status', 'Produit mis à jour.');
    }

    /**
     * Bascule rapide "vedette" (slider du hero) / "nouveauté", directement depuis la liste,
     * sans passer par le formulaire d'édition.
     */
    public function toggleFlag(Request $request, Product $product, string $flag): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $product);

        abort_unless(in_array($flag, ['is_featured', 'is_new', 'is_active', 'is_promo'], true), 404);

        $product->update([$flag => ! $product->$flag]);

        if ($request->ajax()) {
            return response()->json(['status' => 'ok', $flag => $product->$flag]);
        }

        return back();
    }

    /**
     * Mise en promotion depuis la liste : contrairement à "vedette"/"nouveauté" (simples
     * booléens), une promo implique un vrai changement de prix — l'icône ouvre donc une modale
     * plutôt que de basculer un drapeau à l'aveugle.
     */
    public function setPromo(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        if ($request->boolean('remove')) {
            $product->update([
                'price' => $product->old_price ?? $product->price,
                'old_price' => null,
                'is_promo' => false,
            ]);

            return back()->with('status', 'Promotion retirée.');
        }

        $data = $request->validate([
            'mode' => ['required', 'in:percentage,price'],
            'percentage' => ['required_if:mode,percentage', 'nullable', 'integer', 'min:1', 'max:90'],
            'promo_price' => ['required_if:mode,price', 'nullable', 'integer', 'min:0'],
        ]);

        // Si déjà en promo, on repart du prix d'origine (old_price), pas du prix déjà réduit.
        $basePrice = $product->is_promo && $product->old_price ? $product->old_price : $product->price;

        $newPrice = $data['mode'] === 'percentage'
            ? (int) round($basePrice * (1 - $data['percentage'] / 100))
            : (int) $data['promo_price'];

        $product->update([
            'old_price' => $basePrice,
            'price' => $newPrice,
            'is_promo' => true,
        ]);

        return back()->with('status', 'Promotion appliquée.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Produit supprimé.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'model' => ['nullable', 'string', 'max:255'],
            'material' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'old_price' => ['nullable', 'integer', 'min:0'],
            'cost_price' => ['nullable', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_new' => ['sometimes', 'boolean'],
            'is_promo' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        foreach (['is_new', 'is_promo', 'is_featured', 'is_active'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        return $data;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Product::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
