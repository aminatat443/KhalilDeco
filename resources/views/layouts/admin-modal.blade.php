{{-- Habillage "modale flottante" pour les pages de création/modification du back-office.
     Chargée en AJAX (voir x-admin-page-modal) : ne reprend que le contenu, jamais tout le
     châssis admin (sidebar/topbar déjà affichés, jamais rechargés). Navigation directe ou
     rechargement de l'URL : page complète normale, inchangée.

     IMPORTANT : @extends est compilé à part par Blade (footer, indépendant de sa position dans
     le fichier) — un @if autour de @extends ne fonctionne PAS, la valeur doit être une
     expression évaluée à l'exécution, d'où le ternaire ci-dessous plutôt qu'un branchement par
     @if/@else. --}}
@extends(request()->ajax() ? 'layouts.admin-modal-bare' : 'layouts.admin')

@section('bare')
    <div class="w-full @yield('modal-width', 'max-w-xl') mx-auto">
        <div class="max-h-[85vh] overflow-y-auto p-5 sm:p-10">
            @yield('modal')
        </div>
    </div>
@endsection

@section('content')

<div
    x-data="{ show: false }"
    x-init="setTimeout(() => show = true, 10)"
    @keydown.escape.window="if (! window.__nestedOverlayOpen) window.location = '{{ $modalBack ?? route('admin.dashboard') }}'"
    class="fixed inset-0 z-50"
>

    <a
        href="{{ $modalBack ?? route('admin.dashboard') }}"
        x-show="show"
        x-cloak
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-secondary-shade/40"
        aria-label="Fermer"
    ></a>

    <div class="relative flex h-full items-start justify-center overflow-y-auto px-4 py-10 sm:py-16">
        <div
            x-show="show"
            x-cloak
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full @yield('modal-width', 'max-w-xl') overflow-hidden border border-secondary-shade/10 bg-white shadow-sm dark:border-white/10 dark:bg-[#16201f]"
        >

            <a href="{{ $modalBack ?? route('admin.dashboard') }}" class="absolute right-5 top-5 z-10 flex h-9 w-9 items-center justify-center border border-secondary-shade/10 text-secondary-shade/60 transition hover:border-primary/40 hover:text-primary dark:border-white/10 dark:text-white/50 dark:hover:text-primary" aria-label="Fermer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </a>

            <div class="max-h-[85vh] overflow-y-auto p-5 sm:p-10">
                @yield('modal')
            </div>

        </div>
    </div>

</div>

@endsection
