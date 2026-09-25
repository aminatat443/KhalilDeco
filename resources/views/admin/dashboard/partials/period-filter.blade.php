{{-- Filtre de période partagé par tous les tableaux de bord par rôle (section 27 du cahier des
     charges) — soumission en AJAX (ajaxFilter, posé par le conteneur parent), sans rechargement de
     page, cohérent avec le comportement déjà utilisé pour les listes filtrables de l'admin. --}}
<form
    method="GET"
    action="{{ route('admin.dashboard') }}"
    @submit.prevent="submitForm($event)"
    x-data="{ period: '{{ $period->key }}' }"
    class="flex flex-wrap items-center gap-3"
>
    <select name="period" x-model="period" @change="$el.form.requestSubmit()" class="border border-secondary-shade/15 bg-white px-3 py-2 text-xs font-medium uppercase tracking-[0.06em] text-secondary-shade outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
        @foreach(\App\Support\DashboardPeriod::OPTIONS as $key => $label)
            <option value="{{ $key }}" @selected($period->key === $key)>{{ $label }}</option>
        @endforeach
    </select>

    <template x-if="period === 'custom'">
        <div class="flex items-center gap-2">
            <input type="date" name="from" value="{{ $period->from }}" class="border border-secondary-shade/15 bg-white px-2.5 py-2 text-xs text-secondary-shade outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
            <span class="text-xs text-grey dark:text-white/40">au</span>
            <input type="date" name="to" value="{{ $period->to }}" class="border border-secondary-shade/15 bg-white px-2.5 py-2 text-xs text-secondary-shade outline-none focus:border-primary dark:border-white/10 dark:bg-white/5 dark:text-white">
            <button type="submit" class="bg-secondary-shade px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-white transition hover:bg-primary">Appliquer</button>
        </div>
    </template>

    <i x-show="loading" x-cloak class="fa-solid fa-circle-notch fa-spin text-secondary-shade/40 dark:text-white/30"></i>
</form>
