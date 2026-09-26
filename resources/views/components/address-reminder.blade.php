{{-- Rappel pour compléter l'adresse de livraison par défaut — accélère les prochaines
     commandes (checkout pré-rempli automatiquement). Masqué pour le staff, qui n'achète
     pas sur la boutique, et réapparaît à chaque nouvelle session tant que l'adresse
     n'est pas renseignée. --}}
@auth
    @unless(auth()->user()->isStaffMember() || auth()->user()->addresses()->where('is_default', true)->exists())
        <div
            x-data="{ dismissed: false }"
            x-init="dismissed = sessionStorage.getItem('addressReminderDismissed') === '1'"
            x-show="! dismissed"
            x-cloak
            class="border-b border-secondary-shade/10 bg-primary-tint"
        >
            <div class="mx-auto flex max-w-[1440px] flex-wrap items-center justify-between gap-3 px-6 py-3 sm:px-10">
                <p class="text-sm text-primary-shade">
                    <i class="fa-solid fa-location-dot mr-2"></i>
                    Ajoutez votre adresse de livraison à votre profil pour commander plus vite la prochaine fois.
                </p>
                <div class="flex items-center gap-5">
                    <a href="{{ route('account.index') }}#adresse" class="text-xs font-semibold uppercase tracking-[0.1em] text-primary-shade hover:underline">
                        Compléter mon profil
                    </a>
                    <button
                        type="button"
                        @click="dismissed = true; sessionStorage.setItem('addressReminderDismissed', '1')"
                        class="text-primary-shade/60 transition hover:text-primary-shade"
                        aria-label="Fermer"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        </div>
    @endunless
@endauth
