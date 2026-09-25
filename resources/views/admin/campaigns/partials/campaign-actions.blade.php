{{--
    Bloc partagé par les 3 types de campagne : planification (section 9), envoi test (section 8),
    aperçu (section 6) et bouton d'envoi. Doit être inclus À L'INTÉRIEUR du <form> du type
    concerné — s'appuie sur le composant Alpine `campaignForm(type)` posé sur le conteneur parent.
    Variable attendue : $disabled (bool) — désactive l'envoi si aucun produit n'est disponible
    pour une campagne automatique (section 7).
--}}

{{-- Planification --}}
<div class="border border-secondary-shade/10 p-4 dark:border-white/10">
    <p class="mb-3 text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Envoi</p>
    <div class="inline-flex flex-wrap bg-grey-tint p-1 text-xs font-semibold dark:bg-white/5">
        <button type="button" @click="sendMode = 'now'" :class="sendMode === 'now' ? 'bg-secondary-shade text-white' : 'text-grey hover:text-secondary-shade dark:text-white/50 dark:hover:text-white'" class="px-4 py-2 uppercase tracking-[0.08em] transition">Envoyer maintenant</button>
        <button type="button" @click="sendMode = 'schedule'" :class="sendMode === 'schedule' ? 'bg-secondary-shade text-white' : 'text-grey hover:text-secondary-shade dark:text-white/50 dark:hover:text-white'" class="px-4 py-2 uppercase tracking-[0.08em] transition">Programmer l'envoi</button>
    </div>
    <input type="hidden" name="send_mode" :value="sendMode">

    <div x-show="sendMode === 'schedule'" x-cloak class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Date</label>
            <input type="date" name="scheduled_date" x-model="scheduledDate" :min="today" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Heure</label>
            <input type="time" name="scheduled_time" x-model="scheduledTime" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
    </div>
</div>

{{-- Envoi test --}}
<div class="border border-secondary-shade/10 p-4 dark:border-white/10">
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

{{-- Aperçu + envoi --}}
<div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:items-center sm:gap-4">
    <button type="button" @click="preview()" class="w-full px-8 py-4 text-center text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade transition hover:text-primary dark:text-white/70 sm:w-auto">
        <i class="fa-solid fa-eye mr-1.5"></i>Prévisualiser
    </button>
    <button type="submit" @disabled($disabled) class="w-full whitespace-nowrap bg-primary px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto">
        <span x-show="sendMode === 'now'">Envoyer la campagne</span>
        <span x-show="sendMode === 'schedule'" x-cloak>Programmer la campagne</span>
    </button>
</div>
