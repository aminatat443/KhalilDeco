@extends('layouts.admin')

@section('title', "Modèles d'emails")

@section('content')

<h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Campagnes email</h1>
<p class="mt-1 text-sm text-grey dark:text-white/40">Panier abandonné, favoris/stock faible et newsletter — historique et automatisation.</p>

<div class="mt-6 flex flex-wrap gap-2 border-b border-secondary-shade/10 pb-px dark:border-white/10">
    <a href="{{ route('admin.campaigns.index') }}" class="border-b-2 border-transparent px-3 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-grey transition hover:text-secondary-shade dark:text-white/40 dark:hover:text-white">Campagnes</a>
    <a href="{{ route('admin.email-templates.index') }}" class="border-b-2 border-primary px-3 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-primary">Modèles d'emails</a>
    <a href="{{ route('admin.campaigns.automations.edit') }}" class="border-b-2 border-transparent px-3 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-grey transition hover:text-secondary-shade dark:text-white/40 dark:hover:text-white">Paramètres</a>
    <a href="{{ route('admin.campaign-sends.index') }}" class="border-b-2 border-transparent px-3 py-2.5 text-xs font-semibold uppercase tracking-[0.1em] text-grey transition hover:text-secondary-shade dark:text-white/40 dark:hover:text-white">Historique des envois</a>
</div>

<div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach($templates as $template)
        <div class="bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
            <h2 class="text-sm font-semibold text-secondary-shade dark:text-white">{{ App\Models\EmailTemplate::KEYS[$template->key] ?? $template->key }}</h2>
            <p class="mt-2 truncate text-xs text-grey dark:text-white/40">{{ $template->subject }}</p>
            <div class="mt-5 flex items-center gap-4">
                <a href="{{ route('admin.email-templates.edit', $template) }}" class="text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade hover:text-primary dark:text-white/70">
                    <i class="fa-solid fa-pen mr-1.5"></i>Modifier
                </a>
                <a href="{{ route('admin.email-templates.preview', $template) }}" target="_blank" class="text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade hover:text-primary dark:text-white/70">
                    <i class="fa-solid fa-eye mr-1.5"></i>Prévisualiser
                </a>
            </div>
        </div>
    @endforeach
</div>

@endsection
