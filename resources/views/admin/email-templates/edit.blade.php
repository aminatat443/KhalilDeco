@extends('layouts.admin-modal')

@section('title', "Modifier le modèle")

@section('modal-width', 'max-w-2xl')

@section('modal')

@php
    $modalBack = route('admin.email-templates.index');
    $variablesByKey = [
        'abandoned_cart' => ['Prénom', 'Nom', 'NomBoutique', 'LienPanier'],
        'low_stock_favorite' => ['Prénom', 'Nom', 'NomBoutique', 'Produit', 'Stock', 'LienProduit'],
        'promotion' => ['Prénom', 'Nom', 'NomBoutique', 'Produit', 'Prix', 'LienProduit'],
        'active_promotions' => ['Prénom', 'Nom', 'NomBoutique'],
        'new_arrivals' => ['Prénom', 'Nom', 'NomBoutique'],
    ];
    $variables = $variablesByKey[$template->key] ?? [];
@endphp

<h1 class="pr-10 text-2xl font-semibold text-secondary-shade dark:text-white">{{ App\Models\EmailTemplate::KEYS[$template->key] ?? $template->key }}</h1>
<p class="mt-2 text-sm text-grey dark:text-white/40">
    Modifiez le texte envoyé automatiquement. Les informations produit sont ajoutées automatiquement sous ce message.
</p>

@if(count($variables))
    <div class="mt-4 flex flex-wrap gap-2">
        @foreach($variables as $variable)
            <span class="border border-secondary-shade/15 bg-grey-tint px-2.5 py-1 text-[11px] font-medium text-secondary-shade dark:border-white/10 dark:bg-white/5 dark:text-white/70">[{{ $variable }}]</span>
        @endforeach
    </div>
@endif

<div x-data="{ testEmail: '', testStatus: null, testMessage: '', async sendTest() {
        if (! this.testEmail) return;
        this.testStatus = 'sending';
        try {
            const response = await fetch('{{ route('admin.email-templates.send-test', $template) }}', {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': window.csrfToken() },
                body: (() => { const fd = new FormData(); fd.append('test_email', this.testEmail); return fd; })(),
            });
            const data = await response.json();
            this.testStatus = response.ok ? 'success' : 'error';
            this.testMessage = data.message || (response.ok ? 'Email test envoyé.' : `Échec de l'envoi test.`);
        } catch (e) {
            this.testStatus = 'error';
            this.testMessage = 'Erreur réseau — réessayez.';
        }
    } }" class="mt-6 border border-secondary-shade/10 p-4 dark:border-white/10">
    <p class="mb-3 text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Envoyer un email test</p>
    <div class="flex flex-col gap-3 sm:flex-row">
        <input type="email" x-model="testEmail" placeholder="votre@email.com" class="w-full flex-1 border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        <button type="button" @click="sendTest()" :disabled="!testEmail || testStatus === 'sending'" class="shrink-0 whitespace-nowrap border border-secondary-shade/20 px-6 py-2.5 text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/15 dark:text-white/70">
            <span x-show="testStatus !== 'sending'">Envoyer le test</span>
            <span x-show="testStatus === 'sending'" x-cloak>Envoi…</span>
        </button>
    </div>
    <p class="mt-2 text-xs font-semibold" :class="testStatus === 'success' ? 'text-primary' : 'text-red-600 dark:text-red-400'" x-show="testStatus === 'success' || testStatus === 'error'" x-cloak x-text="testMessage"></p>
</div>

<form action="{{ route('admin.email-templates.update', $template) }}" method="POST" class="mt-6 space-y-6">
    @csrf
    @method('PUT')

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Objet</label>
        <input type="text" name="subject" value="{{ old('subject', $template->subject) }}" required maxlength="255" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Titre affiché dans l'email</label>
        <input type="text" name="title" value="{{ old('title', $template->title) }}" maxlength="255" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Contenu</label>
        <textarea name="content" rows="8" required class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">{{ old('content', $template->content) }}</textarea>
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Texte du bouton</label>
        <input type="text" name="button_text" value="{{ old('button_text', $template->button_text) }}" maxlength="100" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    </div>

    <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:items-center sm:gap-4">
        <a href="{{ route('admin.email-templates.preview', $template) }}" target="_blank" class="w-full px-8 py-4 text-center text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade transition hover:text-primary dark:text-white/70 sm:w-auto">
            <i class="fa-solid fa-eye mr-1.5"></i>Prévisualiser
        </a>
        <button type="submit" class="w-full whitespace-nowrap bg-primary px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md sm:w-auto">
            Enregistrer
        </button>
    </div>
</form>

@endsection
