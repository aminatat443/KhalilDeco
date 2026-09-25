@extends('layouts.app')

@section('title', 'Mes commandes — Khalil Déco')

@section('content')
<div x-data="{ returnModal: null }" class="mx-auto max-w-4xl px-6 py-16 sm:px-10">

    <a href="{{ route('account.index') }}" class="text-xs text-grey hover:text-primary"><i class="fa-solid fa-arrow-left mr-1"></i>Mon compte</a>
    <h1 class="mt-3 font-display text-3xl font-normal italic text-secondary-shade sm:text-4xl">Mes commandes</h1>

    <div class="mt-8">
        <x-account-nav active="commandes" />
    </div>

    <div class="mt-8 space-y-3">
        @forelse($orders as $order)
            @php
                $returnInfo = $order->returnStatusInfo();
                $canRequestReturn = ! $order->hasAnyReturn() && $order->status !== 'annulee' && $order->created_at->diffInDays(now()) <= 7;
            @endphp
            <div onclick="window.location='{{ route('account.orders.show', $order) }}'" class="cursor-pointer border border-secondary-shade/10 bg-white p-4 shadow-sm transition hover:border-primary/40 hover:shadow-md sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <span class="font-medium text-secondary-shade">{{ $order->order_number }}</span>
                        <span class="block text-xs text-grey sm:ml-2 sm:inline">{{ $order->created_at->format('d/m/Y') }} · {{ $order->items_count }} article(s)</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 sm:justify-end sm:gap-4">
                        <x-status-pill :tone="$returnInfo['tone'] ?? (\App\Models\Order::STATUS_TONES[$order->status] ?? 'neutral')" :label="$returnInfo['label'] ?? (\App\Models\Order::STATUS_LABELS[$order->status] ?? $order->status)" />
                        <span class="whitespace-nowrap text-sm font-medium text-secondary-shade">{{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
                    </div>
                </div>

                @if($canRequestReturn)
                    <div class="mt-3 border-t border-secondary-shade/10 pt-3">
                        <button
                            type="button"
                            onclick="event.stopPropagation()"
                            @click="returnModal = { id: {{ $order->id }}, number: @js($order->order_number) }"
                            class="text-xs font-semibold text-primary hover:underline"
                        >
                            <i class="fa-solid fa-rotate-left mr-1"></i>Demander un retour
                        </button>
                    </div>
                @elseif($order->status === 'en_attente_paiement')
                    <div class="mt-3 border-t border-secondary-shade/10 pt-3">
                        <a href="{{ route('payment.retry', $order) }}" onclick="event.stopPropagation()" class="text-xs font-semibold text-primary hover:underline">
                            <i class="fa-solid fa-credit-card mr-1"></i>Payer maintenant
                        </a>
                    </div>
                @endif
            </div>
        @empty
            <div class="flex flex-col items-center justify-center border border-dashed border-secondary-shade/15 py-20 text-center">
                <i class="fa-solid fa-bag-shopping mb-4 text-2xl text-secondary-shade/20"></i>
                <p class="text-sm text-grey">Vous n'avez pas encore passé de commande.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-8">{{ $orders->links() }}</div>

    {{-- Modale de demande de retour --}}
    <div x-show="returnModal" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-6">
        <div
            x-show="returnModal"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            @click="returnModal = null"
            class="absolute inset-0 bg-secondary-shade/40"
        ></div>

        <div
            x-show="returnModal"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            @click.outside="returnModal = null"
            class="relative w-full max-w-md bg-white p-8"
        >
            <button type="button" @click="returnModal = null" class="absolute right-6 top-6 text-secondary-shade transition hover:text-primary" aria-label="Fermer">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <h2 class="font-display text-2xl italic text-secondary-shade">Demander un retour</h2>
            <p class="mt-2 text-sm text-grey">Commande <span x-text="returnModal?.number"></span></p>

            <form
                method="POST"
                class="mt-6 space-y-4"
                :action="returnModal ? '/mon-compte/commandes/' + returnModal.id + '/retour' : '#'"
            >
                @csrf
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Motif du retour</label>
                    <select name="reason" required class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm outline-none focus:border-primary">
                        <option value="">Choisir…</option>
                        <option value="taille">Dimensions incorrectes</option>
                        <option value="defaut">Article défectueux</option>
                        <option value="description">Ne correspond pas à la description</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Précisions (optionnel)</label>
                    <textarea name="description" rows="3" class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm outline-none focus:border-primary"></textarea>
                </div>
                <button type="submit" class="w-full bg-secondary-shade px-6 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary">
                    Valider la demande
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
