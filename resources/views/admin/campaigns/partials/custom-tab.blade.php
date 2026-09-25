{{--
    Onglet "Campagne personnalisée" — contenu libre, produits facultatifs (section 4 du cahier
    des charges). Le composant Alpine `campaignForm('newsletter')` est posé sur le conteneur
    parent (voir admin.campaigns.form).
--}}

<form action="{{ route('admin.campaigns.store') }}" method="POST" class="space-y-5" data-confirm="Envoyer cette campagne à tous les abonnés actifs de la newsletter ?" @submit="if (sendMode === 'schedule' && (!scheduledDate || !scheduledTime)) { alert('Choisissez une date et une heure.'); $event.preventDefault(); }">
    @csrf

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Objet de l'email</label>
        <input x-ref="subject" type="text" name="subject" value="{{ old('subject') }}" required maxlength="255" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    </div>

    {{-- Segment de destinataires (section 13/14 du cahier des charges multi-rôles) — "Tous les
         abonnés actifs" reproduit le comportement historique par défaut. --}}
    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">
            Destinataires
            <a href="{{ route('admin.segments.index') }}" target="_blank" class="ml-1 normal-case text-[11px] font-normal text-primary hover:underline">voir les segments</a>
        </label>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <select name="segment" x-model="segment" @change="updateSegmentCount()" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                @foreach(\App\Services\CustomerSegmentService::SEGMENTS as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <select x-show="segment === 'category_buyers'" x-cloak name="segment_category_id" x-model="segmentCategoryId" @change="updateSegmentCount()" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                <option value="">Choisir une catégorie…</option>
                @foreach($categories ?? [] as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <p class="mt-1.5 text-xs text-grey dark:text-white/40">
            <template x-if="segmentCount !== null"><span><span x-text="segmentCount"></span> destinataire(s) actuellement dans ce segment.</span></template>
            <template x-if="segmentCount === null && segment === 'category_buyers'"><span>Choisissez une catégorie pour voir le nombre de destinataires.</span></template>
        </p>
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Titre (optionnel)</label>
        <input x-ref="title" type="text" name="title" value="{{ old('title') }}" maxlength="255" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Contenu</label>
        <textarea x-ref="message" name="message" rows="6" required class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">{{ old('message') }}</textarea>
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Image (optionnelle) — URL</label>
        <input x-ref="image_url" type="url" name="image_url" value="{{ old('image_url') }}" placeholder="https://…" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Texte du bouton (optionnel)</label>
            <input x-ref="button_text" type="text" name="button_text" value="{{ old('button_text') }}" maxlength="100" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Lien du bouton (optionnel)</label>
            <input x-ref="button_url" type="url" name="button_url" value="{{ old('button_url') }}" placeholder="https://…" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
    </div>

    {{-- Produits (facultatif) --}}
    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Produits (facultatif)</label>

        <template x-for="product in selectedProducts" :key="product.id">
            <div class="mb-2 flex items-center gap-3 border border-secondary-shade/10 bg-white p-2.5 dark:border-white/10 dark:bg-[#16201f]">
                <div class="h-10 w-10 shrink-0 bg-grey-tint dark:bg-white/5">
                    <template x-if="product.image"><img :src="product.image" class="h-full w-full object-cover" alt=""></template>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold text-secondary-shade dark:text-white" x-text="product.name"></p>
                    <p class="text-xs text-grey dark:text-white/40" x-text="product.category"></p>
                </div>
                <input type="hidden" name="product_ids[]" :value="product.id">
                <button type="button" @click="removeProduct(product.id)" class="shrink-0 px-2 py-1 text-secondary-shade/50 transition hover:text-red-600" aria-label="Retirer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </template>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <select x-model="selectedCategory" @change="onCategoryChange()" class="border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                <option value="">Toutes les catégories</option>
                <template x-for="category in categoryTree" :key="category.id">
                    <option :value="category.id" x-text="category.name"></option>
                </template>
            </select>
            <select x-model="selectedSubcategory" @change="searchProducts()" :disabled="subcategoryOptions.length === 0" class="border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/10 dark:bg-white/5 dark:text-white">
                <option value="">Toutes les sous-catégories</option>
                <template x-for="subcategory in subcategoryOptions" :key="subcategory.id">
                    <option :value="subcategory.id" x-text="subcategory.name"></option>
                </template>
            </select>
        </div>

        <div class="relative mt-3" @click.outside="productResults = []">
            <input type="text" x-model="productSearch" @input.debounce.300ms="searchProducts()" @focus="searchProducts()" placeholder="Ajouter des produits — nom, référence ou catégorie…" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">

            <div x-show="productResults.length > 0" x-cloak class="absolute z-10 mt-1 max-h-64 w-full overflow-y-auto border border-secondary-shade/15 bg-white shadow-sm dark:border-white/10 dark:bg-[#16201f]">
                <template x-for="product in productResults" :key="product.id">
                    <button type="button" @click="addProduct(product)" class="flex w-full items-center gap-3 border-b border-secondary-shade/10 p-2.5 text-left transition last:border-0 hover:bg-grey-tint dark:border-white/10 dark:hover:bg-white/5">
                        <div class="h-10 w-10 shrink-0 bg-grey-tint dark:bg-white/5">
                            <template x-if="product.image"><img :src="product.image" class="h-full w-full object-cover" alt=""></template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-semibold text-secondary-shade dark:text-white" x-text="product.name"></p>
                            <p class="text-xs text-grey dark:text-white/40" x-text="[product.category, product.model].filter(Boolean).join(' · ')"></p>
                        </div>
                        <p class="shrink-0 text-xs font-semibold text-secondary-shade dark:text-white" x-text="Number(product.price).toLocaleString('fr-FR') + ' FCFA'"></p>
                    </button>
                </template>
            </div>
        </div>
    </div>

    @include('admin.campaigns.partials.campaign-actions', ['disabled' => false])
</form>
