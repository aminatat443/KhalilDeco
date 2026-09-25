@props(['order'])

@php
    $statusLabels = \App\Models\Order::STATUS_LABELS;

    $tones = [
        'neutral' => 'border-secondary-shade/15 text-grey dark:border-white/15 dark:text-white/60',
        'blue' => 'border-blue-200 text-blue-700 dark:border-blue-500/30 dark:text-blue-400',
        'amber' => 'border-tertiary-shade/40 text-tertiary-shade dark:border-tertiary/30 dark:text-tertiary',
        'primary' => 'border-primary/40 text-primary-shade dark:border-primary/40 dark:text-primary',
        'green' => 'border-green-300 text-green-700 dark:border-green-500/30 dark:text-green-400',
        'red' => 'border-red-300 text-red-600 dark:border-red-500/30 dark:text-red-400',
    ];
    $toneClass = $tones[\App\Models\Order::STATUS_TONES[$order->status] ?? 'neutral'];

    // Transitions permises depuis chaque statut — mêmes règles que les boutons d'action de la
    // page détail commande (admin/orders/show.blade.php) : confirmer/annuler ont des effets sur
    // le stock et passent par leurs routes dédiées, les statuts logistiques par la route générique.
    $targets = match($order->status) {
        // Jamais de "Confirmer" ici : le paiement en ligne n'est pas encore validé par le
        // serveur (voir OrderController::confirm, qui refuserait de toute façon). Seule
        // l'annulation manuelle reste possible.
        'en_attente_paiement' => ['annulee'],
        'recue' => ['confirmee', 'annulee'],
        'confirmee', 'en_preparation', 'expediee', 'en_livraison' => collect(['en_preparation', 'expediee', 'livree'])
            ->reject(fn ($s) => $s === $order->status)->push('annulee')->all(),
        default => [],
    };
@endphp

<div x-data="{ open: false }" class="relative inline-block" @click.stop @click.outside="open = false">
    <button
        type="button"
        @if(! empty($targets)) @click="open = ! open" @endif
        class="inline-flex items-center gap-1.5 border px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.08em] transition {{ $toneClass }} {{ empty($targets) ? '' : 'cursor-pointer hover:border-primary hover:text-primary' }}"
    >
        {{ $statusLabels[$order->status] ?? $order->status }}
        @unless(empty($targets))
            <i class="fa-solid fa-chevron-down text-[8px]"></i>
        @endunless
    </button>

    @unless(empty($targets))
        <div x-show="open" x-cloak x-transition.opacity.duration.150ms @click.stop class="absolute left-0 top-full z-20 mt-1 w-44 border border-secondary-shade/10 bg-white py-1 text-left shadow-lg dark:border-white/10 dark:bg-[#16201f]">
            @foreach($targets as $target)
                <form
                    action="{{ $target === 'confirmee' ? route('admin.orders.confirm', $order) : ($target === 'annulee' ? route('admin.orders.cancel', $order) : route('admin.orders.status', $order)) }}"
                    method="POST"
                    @if($target === 'confirmee') data-confirm="Confirmer cette commande ? Le stock sera décrémenté."
                    @elseif($target === 'annulee') data-confirm="Annuler cette commande ?" @endif
                >
                    @csrf
                    @if($target !== 'confirmee' && $target !== 'annulee')
                        <input type="hidden" name="status" value="{{ $target }}">
                    @endif
                    <button type="submit" class="block w-full px-4 py-2 text-left text-xs text-secondary-shade transition hover:bg-grey-tint dark:text-white dark:hover:bg-white/10 {{ $target === 'annulee' ? 'text-red-600 dark:text-red-400' : '' }}">
                        {{ $statusLabels[$target] }}
                    </button>
                </form>
            @endforeach
        </div>
    @endunless
</div>
