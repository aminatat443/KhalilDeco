{{-- Cartes (mobile/tablette) — le tableau à 7 colonnes est illisible en dessous de md,
     on affiche l'essentiel en carte et on renvoie vers la fiche pour le reste. --}}
<div class="space-y-3 lg:hidden">
    @forelse($products as $product)
        <div onclick="window.location='{{ route('admin.products.edit', $product) }}'" class="cursor-pointer bg-white p-4 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
            <div class="flex items-center gap-3">
                <div class="h-14 w-12 shrink-0 overflow-hidden bg-grey-tint dark:bg-white/10">
                    @if($image = $product->images->first()?->url)
                        <img src="{{ img_url($image, 80, 96) }}" alt="" class="h-full w-full object-cover">
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium text-secondary-shade dark:text-white">{{ $product->name }}</p>
                    <p class="text-xs text-grey dark:text-white/50">{{ $product->category->name ?? '—' }}</p>
                </div>
                <x-status-pill :tone="$product->is_active ? 'green' : 'neutral'" :label="$product->is_active ? 'Actif' : 'Désactivé'" class="shrink-0" />
            </div>
            <div class="mt-3 flex items-center justify-between border-t border-secondary-shade/10 pt-3 text-xs dark:border-white/10">
                <span class="text-secondary-shade dark:text-white">
                    {{ number_format($product->price, 0, ',', ' ') }} FCFA
                    @if($product->is_promo && $product->old_price)
                        <span class="ml-1 text-grey/60 line-through dark:text-white/30">{{ number_format($product->old_price, 0, ',', ' ') }}</span>
                    @endif
                </span>
                <span class="text-grey dark:text-white/50">{{ $product->variants_count > 0 ? $product->variants_count.' variante(s)' : $product->stock.' en stock' }}</span>
            </div>
            {{-- Mise en avant : mêmes 3 bascules que la colonne "Vitrine" du tableau desktop --}}
            <div class="mt-3 flex items-center gap-3 border-t border-secondary-shade/10 pt-3" onclick="event.stopPropagation()">
                <button type="button" onclick="toggleProductFlag(event, '{{ route('admin.products.toggle', [$product, 'is_featured']) }}')" title="{{ $product->is_featured ? 'Retirer du slider du hero' : 'Mettre dans le slider du hero' }}" class="flex h-9 w-9 items-center justify-center text-sm transition {{ $product->is_featured ? 'bg-primary-tint text-primary dark:bg-primary/15' : 'text-grey/40 hover:bg-grey-tint hover:text-secondary-shade dark:text-white/30 dark:hover:bg-white/10 dark:hover:text-white' }}">
                    <i class="fa-solid fa-images"></i>
                </button>
                <button type="button" onclick="toggleProductFlag(event, '{{ route('admin.products.toggle', [$product, 'is_new']) }}')" title="{{ $product->is_new ? 'Retirer des nouveautés' : 'Mettre dans les nouveautés' }}" class="flex h-9 w-9 items-center justify-center text-sm transition {{ $product->is_new ? 'bg-primary-tint text-primary dark:bg-primary/15' : 'text-grey/40 hover:bg-grey-tint hover:text-secondary-shade dark:text-white/30 dark:hover:bg-white/10 dark:hover:text-white' }}">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </button>
                <button
                    type="button"
                    @click="promoModal = { id: {{ $product->id }}, name: @js($product->name), price: {{ $product->price }}, oldPrice: {{ $product->old_price ?? 'null' }}, isPromo: {{ $product->is_promo ? 'true' : 'false' }}, mode: 'percentage', percentage: 20, promoPrice: {{ $product->price }} }"
                    title="{{ $product->is_promo ? 'Modifier la promotion' : 'Mettre en promotion' }}"
                    class="flex h-9 w-9 items-center justify-center text-sm transition {{ $product->is_promo ? 'bg-primary-tint text-primary dark:bg-primary/15' : 'text-grey/40 hover:bg-grey-tint hover:text-secondary-shade dark:text-white/30 dark:hover:bg-white/10 dark:hover:text-white' }}"
                >
                    <i class="fa-solid fa-tag"></i>
                </button>
                <a href="{{ route('admin.products.edit', $product) }}" title="Modifier" aria-label="Modifier" class="ml-auto flex h-9 w-9 items-center justify-center text-sm text-secondary-shade hover:text-primary dark:text-white/70">
                    <i class="fa-solid fa-pen"></i>
                </a>
            </div>
        </div>
    @empty
        <div class="flex flex-col items-center gap-3 border border-secondary-shade/10 bg-white px-6 py-14 text-center dark:border-white/10 dark:bg-[#16201f]">
            <span class="flex h-11 w-11 items-center justify-center border border-secondary-shade/10 text-grey/50 dark:border-white/10 dark:text-white/30"><i class="fa-solid fa-boxes-stacked"></i></span>
            <p class="text-sm text-grey dark:text-white/40">Aucun produit ne correspond à ces critères.</p>
        </div>
    @endforelse
</div>

<div class="hidden overflow-x-auto bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10 lg:block">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-secondary-shade/10 text-left text-xs uppercase tracking-[0.1em] text-grey dark:border-white/10 dark:text-white/40">
                <th class="px-6 py-4 font-medium">Produit</th>
                <th class="px-6 py-4 font-medium">Catégorie</th>
                <th class="px-6 py-4 font-medium">Prix</th>
                <th class="px-6 py-4 font-medium">Stock</th>
                <th class="px-6 py-4 font-medium">Statut</th>
                <th class="px-6 py-4 font-medium">Vitrine</th>
                <th class="px-6 py-4 font-medium"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($products as $product)
                <tr onclick="window.location='{{ route('admin.products.edit', $product) }}'" class="cursor-pointer transition hover:bg-grey-tint/40 dark:hover:bg-white/5">
                    <td class="flex items-center gap-3 px-6 py-4">
                        <div class="h-12 w-10 shrink-0 overflow-hidden bg-grey-tint dark:bg-white/10">
                            @if($image = $product->images->first()?->url)
                                <img src="{{ img_url($image, 80, 96) }}" alt="" class="h-full w-full object-cover">
                            @endif
                        </div>
                        <span class="font-medium text-secondary-shade dark:text-white">{{ $product->name }}</span>
                    </td>
                    <td class="px-6 py-4 text-grey dark:text-white/50">{{ $product->category->name ?? '—' }}</td>
                    <td class="whitespace-nowrap px-6 py-4 text-secondary-shade dark:text-white">
                        {{ number_format($product->price, 0, ',', ' ') }} FCFA
                        @if($product->is_promo && $product->old_price)
                            <span class="ml-1 text-xs text-grey/60 line-through dark:text-white/30">{{ number_format($product->old_price, 0, ',', ' ') }}</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-secondary-shade dark:text-white">
                        {{ $product->variants_count > 0 ? $product->variants_count.' variante(s)' : $product->stock }}
                    </td>
                    <td class="px-6 py-4">
                        <x-status-pill :tone="$product->is_active ? 'green' : 'neutral'" :label="$product->is_active ? 'Actif' : 'Désactivé'" />
                    </td>
                    <td class="px-6 py-4" onclick="event.stopPropagation()">
                        <div class="flex items-center gap-3">
                            <button type="button" onclick="toggleProductFlag(event, '{{ route('admin.products.toggle', [$product, 'is_featured']) }}')" title="{{ $product->is_featured ? 'Retirer du slider du hero' : 'Mettre dans le slider du hero' }}" class="flex h-8 w-8 items-center justify-center text-sm transition {{ $product->is_featured ? 'bg-primary-tint text-primary dark:bg-primary/15' : 'text-grey/40 hover:bg-grey-tint hover:text-secondary-shade dark:text-white/30 dark:hover:bg-white/10 dark:hover:text-white' }}">
                                <i class="fa-solid fa-images"></i>
                            </button>
                            <button type="button" onclick="toggleProductFlag(event, '{{ route('admin.products.toggle', [$product, 'is_new']) }}')" title="{{ $product->is_new ? 'Retirer des nouveautés' : 'Mettre dans les nouveautés' }}" class="flex h-8 w-8 items-center justify-center text-sm transition {{ $product->is_new ? 'bg-primary-tint text-primary dark:bg-primary/15' : 'text-grey/40 hover:bg-grey-tint hover:text-secondary-shade dark:text-white/30 dark:hover:bg-white/10 dark:hover:text-white' }}">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                            </button>
                            <button
                                type="button"
                                @click="promoModal = { id: {{ $product->id }}, name: @js($product->name), price: {{ $product->price }}, oldPrice: {{ $product->old_price ?? 'null' }}, isPromo: {{ $product->is_promo ? 'true' : 'false' }}, mode: 'percentage', percentage: 20, promoPrice: {{ $product->price }} }"
                                title="{{ $product->is_promo ? 'Modifier la promotion' : 'Mettre en promotion' }}"
                                class="flex h-8 w-8 items-center justify-center text-sm transition {{ $product->is_promo ? 'bg-primary-tint text-primary dark:bg-primary/15' : 'text-grey/40 hover:bg-grey-tint hover:text-secondary-shade dark:text-white/30 dark:hover:bg-white/10 dark:hover:text-white' }}"
                            >
                                <i class="fa-solid fa-tag"></i>
                            </button>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-right" onclick="event.stopPropagation()">
                        <a href="{{ route('admin.products.edit', $product) }}" title="Modifier" aria-label="Modifier" class="inline-flex h-8 w-8 items-center justify-center text-sm text-secondary-shade hover:text-primary dark:text-white/70"><i class="fa-solid fa-pen"></i></a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-14">
                        <div class="flex flex-col items-center gap-3 text-center">
                            <span class="flex h-11 w-11 items-center justify-center border border-secondary-shade/10 text-grey/50 dark:border-white/10 dark:text-white/30"><i class="fa-solid fa-boxes-stacked"></i></span>
                            <p class="text-sm text-grey dark:text-white/40">Aucun produit ne correspond à ces critères.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6" data-pagination>{{ $products->links() }}</div>
