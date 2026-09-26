@extends('layouts.admin-modal')

@php($modalBack = route('admin.products.index'))

@php($isDraft = $product->name === 'Nouveau produit')

@section('title', $isDraft ? 'Nouveau produit' : 'Modifier le produit')

@section('modal-width', 'max-w-3xl')

@section('modal')

<h1 class="pr-10 text-2xl font-semibold text-secondary-shade dark:text-white">
    {{ $isDraft ? 'Nouveau produit' : 'Modifier « '.$product->name.' »' }}
</h1>
@if($isDraft)
    <p class="mt-2 text-sm text-grey dark:text-white/40">Ce produit reste masqué de la boutique (inactif) tant que vous ne l'activez pas ci-dessous.</p>
@endif

{{-- Champs de base (section 42 du cahier des charges) — enregistrement automatique, aucun bouton --}}
<form
    action="{{ route('admin.products.update', $product) }}"
    method="POST"
    enctype="multipart/form-data"
    class="mt-8 space-y-6"
    x-data="{
        status: 'idle',
        categories: {{ Illuminate\Support\Js::from($categories->map(fn ($c) => ['id' => $c->id, 'label' => ($c->parent?->name ? $c->parent->name.' — ' : '').$c->name])) }},
        categoryQuery: {{ Illuminate\Support\Js::from($product->category ? (($product->category->parent?->name ? $product->category->parent->name.' — ' : '').$product->category->name) : '') }},
        categorySearchOpen: false,
        get categoryMatches() {
            const q = this.categoryQuery.trim().toLowerCase();
            const list = q ? this.categories.filter(c => c.label.toLowerCase().includes(q)) : this.categories;
            return list.slice(0, 30);
        },
        selectCategory(cat) {
            this.$refs.categoryId.value = cat.id;
            this.categoryQuery = cat.label;
            this.categorySearchOpen = false;
            this.saveForm(this.$refs.categoryId.form);
        },
        async saveForm(form) {
            this.status = 'saving';
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': window.csrfToken ? window.csrfToken() : '' },
                    body: new FormData(form),
                });
                this.status = response.ok ? 'saved' : 'error';
            } catch (e) {
                this.status = 'error';
            } finally {
                setTimeout(() => { this.status = 'idle'; }, 2000);
            }
        },
    }"
>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Nom</label>
            <input type="text" name="name" value="{{ old('name', $product->name) }}" required @blur="saveForm($event.target.form)" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>

        <div class="relative sm:col-span-2" @click.outside="categorySearchOpen = false">
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Catégorie</label>
            <input type="hidden" name="category_id" x-ref="categoryId" value="{{ old('category_id', $product->category_id) }}">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-grey/50"></i>
                <input
                    type="text"
                    x-model="categoryQuery"
                    @input="categorySearchOpen = true"
                    @focus="categorySearchOpen = true"
                    placeholder="Rechercher une catégorie…"
                    autocomplete="off"
                    class="w-full border border-secondary-shade/15 bg-white py-2 pl-9 pr-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white"
                >
            </div>

            <div
                x-show="categorySearchOpen"
                x-cloak
                class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto border border-secondary-shade/10 bg-white shadow-lg dark:border-white/10 dark:bg-[#1c2826]"
            >
                <template x-if="categoryMatches.length === 0">
                    <p class="px-4 py-3 text-xs text-grey dark:text-white/40">Aucune catégorie trouvée.</p>
                </template>
                <template x-for="cat in categoryMatches" :key="cat.id">
                    <button
                        type="button"
                        @click="selectCategory(cat)"
                        class="block w-full px-4 py-2.5 text-left text-sm text-secondary-shade transition hover:bg-grey-tint/50 dark:text-white dark:hover:bg-white/5"
                        x-text="cat.label"
                    ></button>
                </template>
            </div>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Prix (FCFA)</label>
            <input type="number" name="price" value="{{ old('price', $product->price) }}" required min="0" @blur="saveForm($event.target.form)" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Ancien prix (optionnel)</label>
            <input type="number" name="old_price" value="{{ old('old_price', $product->old_price) }}" min="0" @blur="saveForm($event.target.form)" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Coût d'achat (optionnel)</label>
            <input type="number" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}" min="0" @blur="saveForm($event.target.form)" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            <p class="mt-1 text-[11px] text-grey dark:text-white/40">Sert uniquement au calcul de la marge dans les tableaux de bord — n'apparaît jamais côté boutique.</p>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">
                Stock
                @if($product->variants->isNotEmpty() ?? false)
                    <span class="normal-case text-grey">(somme des variantes ci-dessous)</span>
                @endif
            </label>
            @if($product->variants->isNotEmpty() ?? false)
                <div x-data="{ stockSum: {{ $product->variants->sum('stock') }} }" @variants-stock-changed.window="stockSum = $event.detail.sum">
                    <input type="hidden" name="stock" :value="stockSum">
                    <div class="w-full cursor-not-allowed select-none border border-secondary-shade/15 bg-grey-tint px-3.5 py-2 text-sm text-grey dark:border-white/10 dark:bg-white/5 dark:text-white/50" x-text="stockSum"></div>
                </div>
            @else
                <input type="number" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" required min="0" @blur="saveForm($event.target.form)" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
            @endif
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Matière (optionnel)</label>
            <input type="text" name="material" value="{{ old('material', $product->material) }}" @blur="saveForm($event.target.form)" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>

        <div class="sm:col-span-2">
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Description</label>
            <textarea name="description" rows="4" @blur="saveForm($event.target.form)" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">{{ old('description', $product->description) }}</textarea>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-x-8 gap-y-3 pt-2">
        @foreach(['is_new' => 'Nouveauté', 'is_promo' => 'Promotion', 'is_featured' => 'Produit vedette', 'is_active' => 'Actif'] as $field => $label)
            <label class="flex items-center gap-2.5 text-sm text-secondary-shade dark:text-white">
                <input
                    type="checkbox"
                    name="{{ $field }}"
                    value="1"
                    @checked(old($field, $product->{$field} ?? ($field === 'is_active')))
                    @change="
                        status = 'saving';
                        fetch('{{ route('admin.products.toggle', [$product, $field]) }}', {
                            method: 'POST',
                            headers: { Accept: 'application/json', 'X-XSRF-TOKEN': window.csrfToken ? window.csrfToken() : '' },
                        }).then((r) => status = r.ok ? 'saved' : 'error').finally(() => setTimeout(() => status = 'idle', 2000));
                    "
                    class="h-4 w-4 text-primary focus:ring-primary"
                >
                {{ $label }}
            </label>
        @endforeach

        <span class="flex items-center gap-1.5 text-xs text-grey dark:text-white/40">
            <i x-show="status === 'saving'" x-cloak class="fa-solid fa-circle-notch fa-spin"></i>
            <span x-show="status === 'saving'" x-cloak>Enregistrement…</span>
            <i x-show="status === 'saved'" x-cloak class="fa-solid fa-check text-green-600 dark:text-green-400"></i>
            <span x-show="status === 'saved'" x-cloak class="text-green-600 dark:text-green-400">Enregistré</span>
            <i x-show="status === 'error'" x-cloak class="fa-solid fa-triangle-exclamation text-red-500"></i>
            <span x-show="status === 'error'" x-cloak class="text-red-500">Échec de l'enregistrement</span>
        </span>
    </div>
</form>

<a href="{{ route('admin.products.index') }}" class="mt-6 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade transition hover:text-primary dark:text-white/70">
    <i class="fa-solid fa-arrow-left text-[10px]"></i>Retour aux produits
</a>

    @can('delete', $product)
        <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="mt-4" onsubmit="return confirm('Supprimer définitivement ce produit ?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-xs font-semibold uppercase tracking-[0.15em] text-red-600 hover:underline dark:text-red-400">Supprimer ce produit</button>
        </form>
    @endcan

    {{-- Images (section 49 du cahier des charges) --}}
    <div class="mt-10 border-t border-secondary-shade/10 pt-8">
        <h2 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-secondary-shade dark:text-white/70">
            <span class="flex h-6 w-6 items-center justify-center bg-primary-tint text-primary dark:bg-primary/15"><i class="fa-solid fa-images text-[10px]"></i></span>
            Images
        </h2>

        <div
            x-data="{
                dragId: null,
                uploadError: null,
                async persist() {
                    const ids = [...this.$refs.grid.querySelectorAll('[data-image-id]')].map(el => el.dataset.imageId);
                    await fetch('{{ route('admin.products.images.reorder', $product) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': window.csrfToken ? window.csrfToken() : '' },
                        body: JSON.stringify({ ids }),
                    });
                },
                async upload(e) {
                    this.uploadError = null;
                    const form = e.target;
                    const body = new FormData(form);
                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: { Accept: 'application/json', 'X-XSRF-TOKEN': window.csrfToken ? window.csrfToken() : '' },
                            body,
                        });
                        const data = await response.json();
                        if (!response.ok) {
                            this.uploadError = Object.values(data.errors ?? {})[0]?.[0] ?? 'Envoi impossible.';
                            return;
                        }
                        this.$refs.grid.innerHTML = data.html;
                    } catch (err) {
                        this.uploadError = 'Envoi impossible.';
                    }
                },
                async setPrimary(e) {
                    const response = await fetch(e.target.action, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-XSRF-TOKEN': window.csrfToken ? window.csrfToken() : '' },
                        body: new FormData(e.target),
                    });
                    if (response.ok) {
                        this.$refs.grid.innerHTML = (await response.json()).html;
                    }
                },
                async destroyImage(e) {
                    if (! confirm('Supprimer cette image ?')) return;
                    const response = await fetch(e.target.action, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-XSRF-TOKEN': window.csrfToken ? window.csrfToken() : '' },
                        body: new FormData(e.target),
                    });
                    if (response.ok) {
                        this.$refs.grid.innerHTML = (await response.json()).html;
                    }
                },
            }"
        >
            <div x-ref="grid" class="mt-5 grid grid-cols-5 gap-3 sm:grid-cols-6 lg:grid-cols-8">
                @include('admin.products.partials.image-grid')
            </div>
            <p x-show="uploadError" x-cloak x-text="uploadError" class="mt-2 text-xs text-red-600 dark:text-red-400"></p>
            <p class="mt-2 text-xs text-grey dark:text-white/40">Glissez une vignette pour changer l'ordre — la première est la photo principale. Cliquez sur la tuile "+" pour choisir plusieurs photos à la fois (ou déposez-les dessus), l'envoi se lance automatiquement.</p>
        </div>
    </div>

    {{-- Variantes (sections 27, 41, 45 du cahier des charges) — mise à jour, création et
         suppression en AJAX : la page ne recharge jamais pour ces actions. --}}
    <div
        class="mt-8 border-t border-secondary-shade/10 pt-8"
        @variants-generated="variants.push(...$event.detail)"
        x-data="{
            variants: {{ Illuminate\Support\Js::from($product->variants->map(fn ($v) => ['id' => $v->id, 'label' => $v->label(), 'sku' => $v->sku, 'stock' => $v->stock])) }},
            savingId: null,
            init() {
                this.$watch('variants', () => {
                    const sum = this.variants.reduce((total, v) => total + (Number(v.stock) || 0), 0);
                    window.dispatchEvent(new CustomEvent('variants-stock-changed', { detail: { sum } }));
                });
            },
            savedId: null,
            async updateStock(variant) {
                this.savingId = variant.id;
                try {
                    const response = await fetch(`{{ url('admin/products/'.$product->id.'/variants') }}/${variant.id}`, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': window.csrfToken() },
                        body: new URLSearchParams({ _method: 'PUT', sku: variant.sku, stock: variant.stock }),
                    });
                    if (response.ok) {
                        this.savedId = variant.id;
                        setTimeout(() => { if (this.savedId === variant.id) this.savedId = null; }, 1500);
                    }
                } finally {
                    this.savingId = null;
                }
            },
            async destroyVariant(variant) {
                if (! confirm(`Supprimer définitivement cette variante « ${variant.label} » ? Pour la remettre simplement en rupture, mettez plutôt son stock à 0.`)) return;
                const response = await fetch(`{{ url('admin/products/'.$product->id.'/variants') }}/${variant.id}`, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': window.csrfToken() },
                    body: new URLSearchParams({ _method: 'DELETE' }),
                });
                if (response.ok) {
                    this.variants = this.variants.filter(v => v.id !== variant.id);
                }
            },
        }"
    >
        <h2 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-secondary-shade dark:text-white/70">
            <span class="flex h-6 w-6 items-center justify-center bg-primary-tint text-primary dark:bg-primary/15"><i class="fa-solid fa-ruler-combined text-[10px]"></i></span>
            Variantes
        </h2>

        <div class="mt-5 divide-y divide-secondary-shade/10 dark:divide-white/10">
            <template x-if="variants.length === 0">
                <p class="py-3 text-sm text-grey dark:text-white/40">Aucune variante — le champ "Stock" ci-dessus s'applique directement au produit.</p>
            </template>
            <template x-for="variant in variants" :key="variant.id">
                <div class="flex flex-col gap-3 py-3 text-sm sm:flex-row sm:items-center sm:gap-4">
                    <span class="min-w-0 text-secondary-shade dark:text-white sm:flex-1">
                        <span x-text="variant.label"></span>
                        <span class="block text-xs text-grey dark:text-white/40 sm:inline">SKU <span x-text="variant.sku"></span></span>
                    </span>
                    <div class="flex items-center justify-between gap-3 sm:shrink-0 sm:justify-start">
                        <div class="flex items-center gap-2">
                            <input type="number" x-model.number="variant.stock" @blur="updateStock(variant)" min="0" class="w-20 border border-secondary-shade/15 bg-white px-3.5 py-1 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                            <span class="w-20 shrink-0 text-xs text-secondary-shade/70 dark:text-white/50">
                                <i x-show="savingId === variant.id" x-cloak class="fa-solid fa-circle-notch fa-spin"></i>
                                <span x-show="savedId === variant.id" x-cloak class="text-green-600 dark:text-green-400"><i class="fa-solid fa-check"></i> Enregistré</span>
                            </span>
                        </div>
                        <button type="button" @click="destroyVariant(variant)" class="ml-2 whitespace-nowrap text-xs text-grey/70 hover:text-red-600 dark:text-white/40 dark:hover:text-red-400 sm:ml-4">Supprimer</button>
                    </div>
                </div>
            </template>
        </div>

        @php($categoryAttributes = $product->category ? $product->category->attributes : collect())

        @if($categoryAttributes->isEmpty())
            <p class="mt-5 text-xs text-grey dark:text-white/40">
                Aucun attribut n'est associé à la catégorie « {{ $product->category?->name }} ».
                <a href="{{ route('admin.categories.edit', $product->category_id) }}" class="text-primary hover:underline">Configurer ses attributs</a>
                pour pouvoir générer des variantes (couleur, puissance, longueur…).
            </p>
        @else
            <form
                @submit.prevent="generate()"
                class="mt-6 space-y-5 border border-dashed border-secondary-shade/20 p-5 dark:border-white/15"
                x-data="{
                    attributes: {{ Illuminate\Support\Js::from($categoryAttributes->map(fn ($a) => [
                        'id' => $a->id,
                        'name' => $a->name,
                        'type' => $a->type,
                        'values' => $a->values->map(fn ($v) => ['id' => $v->id, 'value' => $v->value, 'colorCode' => $v->color_code]),
                    ])) }},
                    selected: {},
                    stocks: {},
                    generating: false,
                    resultMessage: '',
                    async generate() {
                        this.generating = true;
                        this.resultMessage = '';
                        const combos = this.combosList;
                        try {
                            const response = await fetch('{{ route('admin.products.variants.store', $product) }}', {
                                method: 'POST',
                                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': window.csrfToken() },
                                body: JSON.stringify({
                                    variants: combos.map(combo => ({ attribute_value_ids: combo.valueIds, stock: this.stocks[combo.key] })),
                                }),
                            });
                            const data = await response.json();
                            if (response.ok) {
                                this.$dispatch('variants-generated', data.variants);
                                this.resultMessage = data.message;
                                this.selected = {};
                                this.stocks = {};
                            } else {
                                this.resultMessage = data.message || 'Une erreur est survenue.';
                            }
                        } finally {
                            this.generating = false;
                        }
                    },
                    toggleValue(attributeId, valueId) {
                        attributeId = String(attributeId);
                        valueId = String(valueId);
                        const current = this.selected[attributeId] || [];
                        this.selected[attributeId] = current.includes(valueId) ? current.filter(v => v !== valueId) : [...current, valueId];
                    },
                    isSelected(attributeId, valueId) {
                        return (this.selected[String(attributeId)] || []).includes(String(valueId));
                    },
                    get combosList() {
                        const groups = this.attributes.map(a => this.selected[String(a.id)] || []).filter(ids => ids.length > 0);
                        if (! groups.length) return [];
                        let combos = [[]];
                        for (const group of groups) {
                            const next = [];
                            for (const combo of combos) {
                                for (const valueId of group) {
                                    next.push([...combo, valueId]);
                                }
                            }
                            combos = next;
                        }
                        return combos.map(valueIds => {
                            const key = valueIds.slice().sort().join('-');
                            if (! (key in this.stocks)) this.stocks[key] = '';
                            return { key, valueIds };
                        });
                    },
                    valueLabel(valueId) {
                        for (const a of this.attributes) {
                            const v = a.values.find(v => String(v.id) === String(valueId));
                            if (v) return v.value;
                        }
                        return '';
                    },
                    comboLabel(combo) {
                        return combo.valueIds.map(id => this.valueLabel(id)).join(' / ') || '—';
                    },
                }"
            >
                <p class="text-xs text-grey dark:text-white/40"><i class="fa-solid fa-bolt mr-1.5 text-primary"></i>Cochez une ou plusieurs valeurs par attribut : une case de stock apparaît pour chaque combinaison.</p>

                <template x-for="attribute in attributes" :key="attribute.id">
                    <div>
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70" x-text="attribute.name"></label>
                        <div class="flex flex-wrap items-center gap-2">
                            <template x-for="value in attribute.values" :key="value.id">
                                <button
                                    type="button"
                                    @click="toggleValue(attribute.id, value.id)"
                                    class="relative shrink-0 transition"
                                    :class="attribute.type === 'color'
                                        ? ['h-8 w-8', isSelected(attribute.id, value.id) ? 'ring-2 ring-offset-2 ring-primary dark:ring-primary dark:ring-offset-[#16201f]' : 'ring-1 ring-secondary-shade/15 hover:ring-primary/40 dark:ring-white/20 dark:hover:ring-white/50']
                                        : ['flex items-center gap-1.5 border px-3 py-1.5 text-xs font-medium', isSelected(attribute.id, value.id) ? 'border-primary bg-primary text-white' : 'border-secondary-shade/20 text-secondary-shade hover:border-primary/50 dark:border-white/20 dark:text-white/70']"
                                    :style="attribute.type === 'color' ? `background-color: ${value.colorCode}` : ''"
                                    :title="value.value"
                                    :aria-label="value.value"
                                >
                                    <span x-show="attribute.type === 'color' && isSelected(attribute.id, value.id)" x-cloak class="absolute inset-0 flex items-center justify-center text-[10px]" :class="['#ffffff','#fff'].includes((value.colorCode || '').toLowerCase()) ? 'text-secondary-shade' : 'text-white'">
                                        <i class="fa-solid fa-check"></i>
                                    </span>
                                    <template x-if="attribute.type !== 'color'">
                                        <span class="inline-flex items-center gap-1.5">
                                            <i class="fa-solid fa-check text-[10px]" x-show="isSelected(attribute.id, value.id)" x-cloak></i>
                                            <span x-text="value.value"></span>
                                        </span>
                                    </template>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                <div x-show="combosList.length" x-cloak>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Stock par variante</label>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <template x-for="combo in combosList" :key="combo.key">
                            <div class="flex items-center justify-between gap-3 border border-secondary-shade/10 px-3 py-2 dark:border-white/10">
                                <span class="text-xs font-medium text-secondary-shade dark:text-white/80" x-text="comboLabel(combo)"></span>
                                <input
                                    type="number"
                                    x-model.number="stocks[combo.key]"
                                    min="0"
                                    placeholder="Stock"
                                    required
                                    class="w-24 border border-secondary-shade/15 bg-white px-2.5 py-1.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white"
                                >
                            </div>
                        </template>
                    </div>
                </div>

                <p x-show="resultMessage" x-cloak x-text="resultMessage" class="text-xs text-secondary-shade dark:text-white/70"></p>

                <button
                    type="submit"
                    x-show="combosList.length"
                    x-cloak
                    :disabled="generating"
                    class="bg-primary px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md disabled:opacity-50"
                >
                    <i class="fa-solid" :class="generating ? 'fa-circle-notch fa-spin' : 'fa-wand-magic-sparkles'"></i>
                    <span x-show="!generating">Générer (<span x-text="combosList.length"></span>)</span>
                    <span x-show="generating" x-cloak>Génération…</span>
                </button>
            </form>
        @endif
    </div>

@endsection
