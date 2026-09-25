{{--
    Onglet "Nouveautés" ou "Promotions" — produits détectés automatiquement depuis le catalogue
    (sections 2 et 3 du cahier des charges), jamais sélectionnés à la main.
    Variables attendues : $products (collection d'array), $countLabel, $emptyMessage, $template,
    $sendRoute, $kind ('new_arrivals' | 'active_promotions').
--}}

<div class="flex flex-wrap items-center justify-between gap-3 border border-secondary-shade/10 bg-grey-tint/40 px-5 py-4 dark:border-white/10 dark:bg-white/5">
    <div>
        <p class="text-2xl font-semibold text-secondary-shade dark:text-white">{{ $products->count() }}</p>
        <p class="text-xs font-semibold uppercase tracking-[0.1em] text-grey dark:text-white/40">{{ $countLabel }}</p>
    </div>
    @if($products->isEmpty())
        <p class="max-w-xs text-right text-xs font-semibold text-red-600 dark:text-red-400">{{ $emptyMessage }}</p>
    @endif
</div>

@if($products->isNotEmpty())
    <div class="mt-4 grid max-h-72 grid-cols-1 gap-3 overflow-y-auto pr-1 sm:grid-cols-2">
        @foreach($products as $product)
            <div class="flex gap-3 border border-secondary-shade/10 bg-white p-3 dark:border-white/10 dark:bg-[#16201f]">
                <div class="h-16 w-16 shrink-0 bg-grey-tint dark:bg-white/5">
                    @if($product['image'])
                        <img src="{{ img_url($product['image'], 128, 128) }}" alt="" class="h-full w-full object-cover">
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    @if($kind === 'new_arrivals' && !empty($product['category']))
                        <p class="truncate text-[10px] font-semibold uppercase tracking-[0.08em] text-tertiary">{{ $product['category'] }}</p>
                    @endif
                    <p class="truncate text-xs font-semibold text-secondary-shade dark:text-white">{{ $product['name'] }}</p>
                    @if($kind === 'active_promotions')
                        <p class="mt-0.5 text-xs">
                            <span class="text-grey line-through dark:text-white/30">{{ number_format($product['old_price'], 0, ',', ' ') }}</span>
                            <span class="ml-1 font-semibold text-primary">{{ number_format($product['new_price'], 0, ',', ' ') }} FCFA</span>
                            <span class="ml-1 text-[10px] font-semibold text-primary">{{ $product['discount_label'] }}</span>
                        </p>
                    @else
                        <p class="mt-0.5 text-xs font-semibold text-secondary-shade dark:text-white">{{ number_format($product['price'], 0, ',', ' ') }} FCFA</p>
                    @endif
                    <a href="{{ $product['url'] }}" target="_blank" class="mt-1 inline-block text-[10px] font-semibold uppercase tracking-[0.08em] text-secondary-shade underline transition hover:text-primary dark:text-white/60">Voir le produit</a>
                </div>
            </div>
        @endforeach
    </div>
@endif

<form :action="'{{ $sendRoute }}'" method="POST" class="mt-6 space-y-5" data-confirm="Envoyer cette campagne à tous les abonnés actifs de la newsletter ?" @submit="if (sendMode === 'schedule' && (!scheduledDate || !scheduledTime)) { alert('Choisissez une date et une heure.'); $event.preventDefault(); }">
    @csrf

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Objet de l'email</label>
            <input x-ref="subject" type="text" name="subject" value="{{ old('subject', $template->subject) }}" maxlength="255" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Titre affiché (optionnel)</label>
            <input x-ref="title" type="text" name="title" value="{{ old('title', $template->title) }}" maxlength="255" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Message d'introduction (optionnel)</label>
        <textarea x-ref="message" name="message" rows="3" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">{{ old('message', $template->content) }}</textarea>
        <p class="mt-1.5 text-xs text-grey dark:text-white/40">Les produits ci-dessus sont ajoutés automatiquement sous ce message — inutile de les décrire.</p>
    </div>

    @include('admin.campaigns.partials.campaign-actions', ['disabled' => $products->isEmpty()])
</form>
