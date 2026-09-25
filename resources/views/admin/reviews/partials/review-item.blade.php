@php($showApprove ??= false)

<div class="py-4" x-data="{ replying: false }">
    <div class="flex items-start justify-between gap-4">
        <div class="flex-1">
            <div class="flex items-center gap-2">
                <p class="text-sm font-medium text-secondary-shade dark:text-white">{{ $review->user->name }}</p>
                <span class="text-xs text-grey dark:text-white/40">— {{ $review->product->name }}</span>
                <span class="flex gap-0.5">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="fa-solid fa-star text-[10px] {{ $i <= $review->rating ? 'text-primary' : 'text-grey-tint dark:text-white/15' }}"></i>
                    @endfor
                </span>
            </div>
            @if($review->comment)
                <p class="mt-1.5 text-sm text-grey dark:text-white/50">{{ $review->comment }}</p>
            @endif

            @if($review->admin_reply)
                <div class="mt-3 border-l-2 border-primary/30 bg-primary-tint/20 py-2 pl-3 dark:bg-primary/5">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-primary-shade dark:text-primary">Réponse de Khalil Déco</p>
                    <p class="mt-1 text-sm text-secondary-shade dark:text-white/80">{{ $review->admin_reply }}</p>
                </div>
            @endif

            @can('reply', $review)
                <div class="mt-2" x-show="! replying">
                    <button type="button" @click="replying = true" class="text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade hover:text-primary dark:text-white/60">
                        {{ $review->admin_reply ? 'Modifier la réponse' : 'Répondre' }}
                    </button>
                </div>

                <form x-show="replying" x-cloak action="{{ route('admin.reviews.reply', $review) }}" method="POST" class="mt-3 space-y-2">
                    @csrf
                    <textarea name="admin_reply" rows="2" placeholder="Votre réponse publique à ce client…" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">{{ $review->admin_reply }}</textarea>
                    <div class="flex items-center gap-3">
                        <button type="submit" class="text-xs font-semibold uppercase tracking-[0.1em] text-primary hover:underline">Publier la réponse</button>
                        <button type="button" @click="replying = false" class="text-xs text-grey hover:text-secondary-shade dark:text-white/40 dark:hover:text-white">Annuler</button>
                    </div>
                </form>
            @endcan
        </div>
        <div class="flex shrink-0 gap-3">
            @if($showApprove)
                <form action="{{ route('admin.reviews.approve', $review) }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs font-semibold uppercase tracking-[0.1em] text-primary hover:underline">Publier</button>
                </form>
            @endif
            <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" onsubmit="return confirm('Supprimer cet avis ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs font-semibold uppercase tracking-[0.1em] text-grey hover:text-secondary-shade dark:text-white/40 dark:hover:text-white">{{ $showApprove ? 'Rejeter' : 'Supprimer' }}</button>
            </form>
        </div>
    </div>
</div>
