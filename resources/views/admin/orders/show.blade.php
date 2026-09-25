@extends('layouts.admin-modal')

@php
    $modalBack = route('admin.orders.index');
@endphp

@section('title', $order->order_number)

@section('modal-width', 'max-w-4xl')

@section('modal')

{{-- Toute la fiche (badge de statut, suivi, actions) est ré-affichée en place après chaque
     changement de statut — la même vue Blade est rendue côté serveur (aucune duplication de
     logique) et injectée via innerHTML, pas de rechargement de page. --}}
<div
    x-data="{
        busy: false,
        async submitStatusForm(e) {
            const form = e.target.closest('form');
            if (! form) return;
            const confirmMessage = form.dataset.confirm;
            if (confirmMessage && ! confirm(confirmMessage)) return;

            this.busy = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-XSRF-TOKEN': window.csrfToken() },
                    body: new FormData(form),
                });
                const data = await response.json();
                if (response.ok) {
                    this.$refs.content.innerHTML = data.html;
                } else {
                    alert(data.message || 'Une erreur est survenue.');
                }
            } catch (e) {
                alert('Une erreur est survenue.');
            } finally {
                this.busy = false;
            }
        },
    }"
    @submit.prevent="submitStatusForm($event)"
    class="relative"
    data-ajax-status-form
>
    <div x-show="busy" x-cloak class="absolute inset-0 z-10 flex items-start justify-center bg-white/70 pt-16 dark:bg-[#16201f]/70">
        <i class="fa-solid fa-circle-notch fa-spin text-2xl text-primary"></i>
    </div>
    <div x-ref="content" :class="busy && 'opacity-50 pointer-events-none'" class="transition-opacity">
        @include('admin.orders.partials.detail')
    </div>
</div>

@endsection
