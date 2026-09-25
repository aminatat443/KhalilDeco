{{--
    Lien "Réinitialiser" — affiché dès qu'au moins un filtre (autre que "page") est actif.
    L'état initial vient de l'URL courante (rendu serveur), mais reste ensuite piloté par
    `hasFilters` du composant Alpine `ajaxFilter()` parent, qui le recalcule à chaque changement
    de filtre en AJAX : sans ça, le lien resterait figé tel qu'au premier chargement de la page.
--}}
@props(['route'])

<a
    href="{{ $route }}"
    x-show="hasFilters"
    x-cloak
    @if(collect(request()->query())->except('page')->filter()->isEmpty()) style="display: none" @endif
    class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold uppercase tracking-[0.1em] text-grey transition hover:text-primary dark:text-white/40 dark:hover:text-primary"
>
    <i class="fa-solid fa-xmark"></i>Réinitialiser
</a>
