@php
    $statusLabels = \App\Models\Order::STATUS_LABELS;
@endphp

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0">
        <a href="{{ route('admin.orders.index') }}" class="text-xs text-grey hover:text-primary dark:text-white/40"><i class="fa-solid fa-arrow-left mr-1"></i>Commandes</a>
        <h1 class="mt-2 pr-10 text-2xl font-semibold text-secondary-shade dark:text-white sm:text-3xl">
            {{ $order->order_number }}
            @if($order->is_in_store)
                <span class="ml-2 align-middle text-xs font-sans not-italic font-semibold uppercase tracking-[0.08em] text-grey dark:text-white/40"><i class="fa-solid fa-store mr-1"></i>Vente en boutique</span>
            @endif
        </h1>
    </div>
    <div class="flex items-center justify-between gap-4 sm:justify-end">
        <button type="button" @click="window.dispatchEvent(new CustomEvent('open-invoice-preview', { detail: { url: '{{ $order->invoiceUrl() }}', label: '{{ $order->order_number }}', phone: '{{ $order->customer_phone }}' } }))" class="whitespace-nowrap text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade hover:text-primary dark:text-white/70">
            <i class="fa-solid fa-file-invoice mr-1.5"></i>Facture
        </button>
        <x-order-status-badge :order="$order" />
    </div>
</div>

{{-- Historique du statut — dérivé de l'étape actuelle (aucun horodatage par transition n'est
     conservé en base, donc affiché comme un parcours plutôt qu'un journal daté). --}}
<div class="mt-6 border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-white/5">
    <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Suivi de la commande</h2>
    @php
        $pipeline = ['recue', 'confirmee', 'en_preparation', 'expediee', 'livree'];
        $currentIndex = array_search($order->status, $pipeline, true);

        // Mêmes transitions autorisées que le menu de statut (x-order-status-badge) — cocher une
        // étape à venir déclenche exactement la même action que la choisir dans ce menu (la
        // confirmation décrémente le stock et passe par sa route dédiée, les étapes logistiques
        // par la route générique). Aucune nouvelle règle métier, juste un second accès à celles
        // qui existent déjà.
        $pipelineTargets = match($order->status) {
            // Jamais de "Confirmer" ici tant que le paiement en ligne n'est pas validé par le
            // serveur — voir OrderController::confirm, qui la refuserait de toute façon.
            'en_attente_paiement' => ['annulee'],
            'recue' => ['confirmee', 'annulee'],
            'confirmee', 'en_preparation', 'expediee', 'en_livraison' => collect(['en_preparation', 'expediee', 'livree'])
                ->reject(fn ($s) => $s === $order->status)->push('annulee')->all(),
            default => [],
        };
    @endphp
    @if($order->status === 'annulee')
        <p class="mt-4 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
            <i class="fa-solid fa-xmark"></i> Commande annulée
        </p>
    @elseif($order->status === 'en_attente_paiement')
        <p class="mt-4 flex items-center gap-2 text-sm text-tertiary-shade">
            <i class="fa-solid fa-hourglass-half"></i> En attente de paiement — le suivi logistique commence une fois le paiement confirmé par le serveur.
        </p>
        @can('cancel', $order)
            <form action="{{ route('admin.orders.cancel', $order) }}" method="POST" class="mt-3" data-confirm="Annuler cette commande en attente de paiement ?">
                @csrf
                <button type="submit" class="text-xs font-semibold uppercase tracking-[0.1em] text-red-600 hover:underline dark:text-red-400">Annuler la commande</button>
            </form>
        @endcan
    @else
        <div class="mt-5 -mx-6 overflow-x-auto px-6 pb-1 sm:mx-0 sm:overflow-visible sm:px-0 sm:pb-0">
        <div class="flex min-w-[420px] items-start sm:min-w-0">
            @foreach($pipeline as $i => $step)
                @php
                    $clickable = in_array($step, $pipelineTargets, true);
                    $reached = $i <= $currentIndex;
                    // L'état visuel (atteinte ou non) reste déterminé par la position dans le
                    // parcours, indépendamment du fait que l'étape soit cliquable — sinon une
                    // étape déjà dépassée mais toujours proposée comme cible (les statuts
                    // logistiques peuvent être réassignés dans n'importe quel ordre) semblait
                    // "revenir en arrière" visuellement au lieu de rester marquée acquise.
                    $checkClasses = $reached
                        ? 'border-primary bg-primary text-white'
                        : 'border-secondary-shade/15 text-grey/50 dark:border-white/15 dark:text-white/30';
                @endphp
                <div class="flex flex-1 flex-col items-center text-center last:flex-none">
                    <div class="flex w-full items-center">
                        <span class="h-px flex-1 {{ $i === 0 ? 'invisible' : ($reached ? 'bg-primary' : 'bg-secondary-shade/10 dark:bg-white/10') }}"></span>
                        @if($clickable)
                            <form
                                action="{{ $step === 'confirmee' ? route('admin.orders.confirm', $order) : route('admin.orders.status', $order) }}"
                                method="POST"
                                @if($step === 'confirmee') data-confirm="Confirmer cette commande ? Le stock sera décrémenté." @endif
                            >
                                @csrf
                                @if($step !== 'confirmee')
                                    <input type="hidden" name="status" value="{{ $step }}">
                                @endif
                                <button
                                    type="submit"
                                    title="Marquer comme « {{ $statusLabels[$step] }} »"
                                    class="flex h-6 w-6 shrink-0 cursor-pointer items-center justify-center border text-[10px] transition hover:border-primary hover:bg-primary hover:text-white {{ $checkClasses }}"
                                >
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </form>
                        @else
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center border text-[10px] {{ $checkClasses }}">
                                <i class="fa-solid fa-check"></i>
                            </span>
                        @endif
                        <span class="h-px flex-1 {{ $i === count($pipeline) - 1 ? 'invisible' : ($i < $currentIndex ? 'bg-primary' : 'bg-secondary-shade/10 dark:bg-white/10') }}"></span>
                    </div>
                    <span class="mt-2 block w-full px-0.5 text-[9px] font-semibold uppercase leading-tight tracking-[0.04em] sm:text-[10px] sm:tracking-[0.06em] {{ $reached ? 'text-secondary-shade dark:text-white' : 'text-grey/50 dark:text-white/30' }}">{{ $statusLabels[$step] }}</span>
                </div>
            @endforeach
        </div>
        </div>
    @endif
</div>

<div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-[1fr_320px]">

    <div class="space-y-6">

        {{-- Articles --}}
        <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Articles</h2>
            <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
                @foreach($order->items as $item)
                    <div class="flex items-center justify-between gap-3 py-3 text-sm">
                        <div class="min-w-0">
                            <p class="break-words font-medium text-secondary-shade dark:text-white">{{ $item->product_name }}</p>
                            @if($item->variant_label)
                                <p class="text-xs text-grey dark:text-white/40">{{ $item->variant_label }}</p>
                            @endif
                            <p class="text-xs text-grey dark:text-white/40">Qté : {{ $item->quantity }} · SKU {{ $item->sku ?? '—' }}</p>
                        </div>
                        <p class="shrink-0 whitespace-nowrap font-medium text-secondary-shade dark:text-white">{{ number_format($item->subtotal, 0, ',', ' ') }} FCFA</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 space-y-1.5 border-t border-secondary-shade/10 pt-4 text-sm text-grey dark:border-white/10 dark:text-white/50">
                <div class="flex justify-between"><span>Sous-total</span><span>{{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</span></div>
                <div class="flex justify-between"><span>Livraison{{ $order->delivery_zone ? ' ('.$order->delivery_zone.')' : '' }}</span><span>{{ number_format($order->delivery_fee, 0, ',', ' ') }} FCFA</span></div>
                @if($order->discount > 0)
                    <div class="flex justify-between text-primary"><span>Réduction {{ $order->coupon?->code }}</span><span>-{{ number_format($order->discount, 0, ',', ' ') }} FCFA</span></div>
                @endif
            </div>
            <div class="mt-2 flex justify-between border-t border-secondary-shade/10 pt-2 text-sm font-semibold text-secondary-shade dark:border-white/10 dark:text-white">
                <span>Total</span><span>{{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
            </div>
        </div>

        {{-- Client & livraison --}}
        <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Client & livraison</h2>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-grey dark:text-white/40">Nom</dt><dd class="text-secondary-shade dark:text-white">{{ $order->customer_name }}</dd></div>
                <div class="flex justify-between"><dt class="text-grey dark:text-white/40">Téléphone</dt><dd class="text-secondary-shade dark:text-white">{{ $order->customer_phone }}</dd></div>
                <div class="flex justify-between"><dt class="text-grey dark:text-white/40">Email</dt><dd class="text-secondary-shade dark:text-white">{{ $order->customer_email }}</dd></div>
                <div class="flex justify-between">
                    <dt class="text-grey dark:text-white/40">Adresse</dt>
                    <dd class="text-right text-secondary-shade dark:text-white">
                        @if($order->is_in_store)
                            Retrait en boutique
                        @else
                            {{ $order->delivery_address }}, {{ $order->delivery_quartier }}<br>{{ $order->delivery_city }}, {{ $order->delivery_region }}
                        @endif
                    </dd>
                </div>
                @if($order->delivery_instructions)
                    <div class="flex justify-between"><dt class="text-grey dark:text-white/40">Instructions</dt><dd class="text-secondary-shade dark:text-white">{{ $order->delivery_instructions }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-grey dark:text-white/40">Paiement</dt><dd class="text-secondary-shade dark:text-white">{{ $order->paymentMethodLabel() }}</dd></div>
            </dl>
        </div>

    </div>

    {{-- Actions --}}
    <div class="space-y-4">
        <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Actions</h2>

            @if($order->status === 'recue')
                <form action="{{ route('admin.orders.confirm', $order) }}" method="POST" class="mt-4" data-confirm="Confirmer cette commande ? Le stock sera décrémenté.">
                    @csrf
                    <button type="submit" class="w-full bg-primary py-3 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary-shade">
                        Confirmer la commande
                    </button>
                </form>
            @endif

            @if(! in_array($order->status, ['annulee', 'livree']))
                <form action="{{ route('admin.orders.cancel', $order) }}" method="POST" class="mt-4" data-confirm="Annuler cette commande ?">
                    @csrf
                    <button type="submit" class="w-full border border-red-300 py-3 text-xs font-semibold uppercase tracking-[0.15em] text-red-600 transition hover:bg-red-600 hover:text-white dark:border-red-500/30 dark:text-red-400">
                        Annuler la commande
                    </button>
                </form>
            @endif
        </div>

        {{-- Paiements — enregistrement manuel d'un encaissement, vérification, remboursement --}}
        <div class="border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Paiements</h2>
                @php($collected = $order->payments->where('status', 'success')->sum('amount'))
                <span class="text-xs text-grey dark:text-white/40">{{ number_format($collected, 0, ',', ' ') }} / {{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
            </div>

            <div class="mt-3 space-y-2">
                @forelse($order->payments as $payment)
                    @php($tones = ['pending' => 'text-amber-600 dark:text-amber-400', 'processing' => 'text-blue-600 dark:text-blue-400', 'success' => 'text-primary', 'failed' => 'text-red-600 dark:text-red-400', 'cancelled' => 'text-grey dark:text-white/40', 'refunded' => 'text-grey dark:text-white/40'])
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-secondary-shade dark:text-white">
                            {{ \App\Models\Order::PAYMENT_METHOD_LABELS[$payment->provider] ?? $payment->provider }}
                            <span class="text-xs {{ $tones[$payment->status] }}">— {{ \App\Models\Payment::STATUS_LABELS[$payment->status] }}</span>
                            @if($payment->gateway)
                                <span class="text-[11px] text-grey/60 dark:text-white/30">({{ $payment->transaction_id }})</span>
                            @endif
                        </span>
                        <span class="font-medium text-secondary-shade dark:text-white">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</span>
                    </div>
                    @can('verify', \App\Models\Payment::class)
                        @if($payment->status === 'pending')
                            <div class="flex gap-2 pl-0.5">
                                <form action="{{ route('admin.payments.verify', $payment) }}" method="POST"><input type="hidden" name="result" value="success">@csrf<button class="text-[11px] font-semibold uppercase text-primary hover:underline">Confirmer</button></form>
                                <form action="{{ route('admin.payments.verify', $payment) }}" method="POST"><input type="hidden" name="result" value="failed">@csrf<button class="text-[11px] font-semibold uppercase text-red-600 hover:underline dark:text-red-400">Rejeter</button></form>
                            </div>
                        @endif
                    @endcan
                    @can('refund', \App\Models\Payment::class)
                        @if($payment->status === 'success')
                            <form action="{{ route('admin.payments.refund', $payment) }}" method="POST" class="pl-0.5" data-confirm="Rembourser ce paiement de {{ number_format($payment->amount, 0, ',', ' ') }} FCFA ?">
                                @csrf
                                <button class="text-[11px] font-semibold uppercase text-grey hover:text-red-600 dark:text-white/40 dark:hover:text-red-400">Rembourser</button>
                            </form>
                        @endif
                    @endcan
                @empty
                    <p class="text-sm text-grey dark:text-white/40">Aucun paiement enregistré.</p>
                @endforelse
            </div>

            @can('create', \App\Models\Payment::class)
                @if($collected < $order->total)
                    <form action="{{ route('admin.payments.store', $order) }}" method="POST" class="mt-4 space-y-3 border-t border-secondary-shade/10 pt-4 dark:border-white/10">
                        @csrf
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.08em] text-grey dark:text-white/40">Montant (FCFA)</label>
                            <input type="number" name="amount" min="1" max="{{ $order->total }}" value="{{ $order->total - $collected }}" required class="w-full border border-secondary-shade/15 bg-white px-3 py-2 text-sm outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.08em] text-grey dark:text-white/40">Moyen</label>
                            <select name="provider" class="w-full border border-secondary-shade/15 bg-white px-3 py-2 text-sm outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
                                @foreach(\App\Models\Order::PAYMENT_METHOD_LABELS as $value => $label)
                                    <option value="{{ $value }}" @selected($order->payment_method === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="status" value="success">
                        <button type="submit" class="w-full bg-secondary-shade py-2.5 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary">
                            Enregistrer le paiement
                        </button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

</div>
