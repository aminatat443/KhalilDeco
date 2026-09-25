<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AttributeController extends Controller
{
    /**
     * Attributs de produit dynamiques (Couleur, Puissance, Longueur...) et leurs valeurs —
     * chaque catégorie choisit ensuite lesquels s'appliquent à ses produits (voir
     * CategoryController) plutôt que d'avoir les mêmes champs figés pour tout le catalogue.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Attribute::class);

        $attributes = Attribute::withCount('values')->with('values')->orderBy('sort_order')->get();

        return view('admin.attributes.index', ['attributes' => $attributes]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Attribute::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:select,color'],
        ]);

        Attribute::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'type' => $data['type'],
        ]);

        return back()->with('status', 'Attribut créé.');
    }

    public function destroy(Attribute $attribute): RedirectResponse
    {
        $this->authorize('delete', $attribute);

        $attribute->delete();

        return back()->with('status', 'Attribut supprimé.');
    }

    /**
     * Ajout d'une valeur à un attribut (ex. "Blanc" / #FFFFFF pour l'attribut Couleur).
     */
    public function storeValue(Request $request, Attribute $attribute): RedirectResponse
    {
        $this->authorize('update', $attribute);

        $data = $request->validate([
            'value' => ['required', 'string', 'max:255'],
            'color_code' => ['nullable', 'string', 'max:7'],
        ]);

        $attribute->values()->create([
            'value' => $data['value'],
            'color_code' => $attribute->type === 'color' ? ($data['color_code'] ?? '#cccccc') : null,
            'sort_order' => $attribute->values()->max('sort_order') + 1,
        ]);

        return back()->with('status', 'Valeur ajoutée.');
    }

    public function destroyValue(Attribute $attribute, AttributeValue $value): RedirectResponse
    {
        $this->authorize('update', $attribute);

        abort_unless($value->attribute_id === $attribute->id, 404);

        $value->delete();

        return back()->with('status', 'Valeur supprimée.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Attribute::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
