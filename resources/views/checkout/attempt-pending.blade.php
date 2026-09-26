@extends('layouts.app')

@section('title', 'Vérification du paiement — Khalil Déco')

@section('content')
<div
    x-data="{
        attemptId: {{ $attempt->id }},
        status: '{{ $attempt->status }}',
        poll: null,
        async check() {
            try {
                const res = await fetch(`/commande/tentative/${this.attemptId}/statut`, { headers: { Accept: 'application/json' } });
                if (! res.ok) return;
                const data = await res.json();
                if (data.order_id) {
                    clearInterval(this.poll);
                    window.location = `/commande/${data.order_id}/confirmation`;
                    return;
                }
                this.status = data.status;
                if (['cancelled', 'failed'].includes(data.status)) {
                    clearInterval(this.poll);
                }
            } catch (e) {}
        },
    }"
    x-init="poll = setInterval(() => check(), 3000)"
    class="mx-auto max-w-lg px-6 py-24 text-center sm:px-10"
>
    <template x-if="! ['cancelled', 'failed'].includes(status)">
        <div>
            <i class="fa-solid fa-circle-notch fa-spin text-3xl text-primary"></i>
            <h1 class="mt-6 font-display text-3xl font-normal italic text-secondary-shade">Vérification du paiement…</h1>
            <p class="mt-3 text-sm text-grey">
                Votre paiement est en cours de confirmation. Cette page se met à jour automatiquement — ne la fermez pas.
            </p>
        </div>
    </template>

    <template x-if="['cancelled', 'failed'].includes(status)">
        <div>
            <i class="fa-solid fa-circle-xmark text-3xl text-red-500"></i>
            <h1 class="mt-6 font-display text-3xl font-normal italic text-secondary-shade">Le paiement n'a pas abouti</h1>
            <p class="mt-3 text-sm text-grey">
                Aucune commande n'a été créée. Votre panier est toujours disponible — vous pouvez réessayer.
            </p>
            <a href="{{ route('checkout.index') }}" class="mt-8 inline-block bg-secondary-shade px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary">
                Retour au paiement
            </a>
        </div>
    </template>
</div>
@endsection
