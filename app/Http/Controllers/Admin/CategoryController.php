<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Gestion des catégories (section 43 du cahier des charges).
     */
    public function index(): View
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::whereNull('parent_id')
            ->withCount('products')
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')->withCount('products')])
            ->orderBy('sort_order')
            ->get();

        return view('admin.categories.index', ['categories' => $categories]);
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('admin.categories.form', [
            'category' => new Category(),
            'universes' => Category::whereNull('parent_id')->orderBy('sort_order')->get(),
            'attributes' => Attribute::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Category::class);

        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $category = Category::create($data);
        $category->attributes()->sync($request->input('attribute_ids', []));

        return redirect()->route('admin.categories.index')->with('status', 'Catégorie créée.');
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('admin.categories.form', [
            'category' => $category,
            'universes' => Category::whereNull('parent_id')->where('id', '!=', $category->id)->orderBy('sort_order')->get(),
            'attributes' => Attribute::orderBy('sort_order')->get(),
            'selectedAttributeIds' => $category->attributes()->pluck('attributes.id')->all(),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $data = $this->validated($request, $category->id);

        if ($data['name'] !== $category->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $category->id);
        }

        $category->update($data);
        $category->attributes()->sync($request->input('attribute_ids', []));

        return redirect()->route('admin.categories.index')->with('status', 'Catégorie mise à jour.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        if ($category->products()->exists() || $category->children()->exists()) {
            return back()->withErrors(['category' => 'Impossible de supprimer une catégorie contenant des produits ou des sous-catégories.']);
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('status', 'Catégorie supprimée.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        if ($ignoreId && (int) ($data['parent_id'] ?? 0) === $ignoreId) {
            abort(422, "Une catégorie ne peut pas être sa propre catégorie parente.");
        }

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Category::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
