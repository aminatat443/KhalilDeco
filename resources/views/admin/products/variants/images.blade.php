{{-- Photos d'une variante exacte (ex. "Blanc / 12W") — ouverte en modale flottante AJAX depuis
     la fiche produit (bouton "Photos" sur la ligne de la variante). Revenir (X / Échap / retour
     navigateur) réaffiche la fiche produit dans la même modale, jamais un rechargement de page
     (voir x-admin-page-modal et la condition request()->ajax() dans layouts/admin-modal.blade.php). --}}
@extends('layouts.admin-modal')

@php($modalBack = route('admin.products.edit', $product))

@section('title', 'Photos de la variante — '.$product->name)

@section('modal-width', 'max-w-3xl')

@section('modal')

<a href="{{ $modalBack }}" onclick="event.preventDefault(); window.openAdminModal(this.href)" class="inline-flex items-center gap-2 text-xs font-medium text-grey transition hover:text-primary dark:text-white/50">
    <i class="fa-solid fa-arrow-left"></i> Retour à « {{ $product->name }} »
</a>

<h1 class="mt-4 pr-10 text-2xl font-semibold text-secondary-shade dark:text-white">
    Photos — {{ $variant->label() }}
</h1>
<p class="mt-2 text-sm text-grey dark:text-white/40">
    SKU {{ $variant->sku }}. Ces photos remplacent la galerie générique du produit uniquement quand un client sélectionne cette combinaison précise ; les autres variantes n'en sont pas affectées.
</p>

<div class="mt-8 border-t border-secondary-shade/10 pt-8 dark:border-white/10">
    <x-admin-image-manager :product="$product" :variant="$variant" :images="$variant->images" />
</div>

@endsection
