<div class="bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
    <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">
        En attente de modération <span class="text-grey dark:text-white/40">({{ $pending->total() }})</span>
    </h2>

    <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
        @forelse($pending as $review)
            @include('admin.reviews.partials.review-item', ['review' => $review, 'showApprove' => true])
        @empty
            <p class="py-4 text-sm text-grey dark:text-white/40">Aucun avis en attente.</p>
        @endforelse
    </div>

    <div class="mt-4" data-pagination>{{ $pending->links() }}</div>
</div>

<div class="mt-6 bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
    <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">
        Publiés récemment <span class="text-grey dark:text-white/40">({{ $approved->total() }})</span>
    </h2>

    <div class="mt-4 divide-y divide-secondary-shade/10 dark:divide-white/10">
        @forelse($approved as $review)
            @include('admin.reviews.partials.review-item', ['review' => $review, 'showApprove' => false])
        @empty
            <p class="py-4 text-sm text-grey dark:text-white/40">Aucun avis publié pour le moment.</p>
        @endforelse
    </div>

    <div class="mt-4" data-pagination>{{ $approved->links() }}</div>
</div>
