<div class="space-y-3 lg:hidden">
    @forelse($coupons as $coupon)
        <a href="{{ route('admin.coupons.edit', $coupon) }}" class="block bg-white p-4 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
            <div class="flex items-center justify-between gap-3">
                <span class="font-medium text-secondary-shade dark:text-white">{{ $coupon->code }}</span>
                <x-status-pill :tone="$coupon->is_active ? 'green' : 'neutral'" :label="$coupon->is_active ? 'Actif' : 'Désactivé'" />
            </div>
            <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2.5 border-t border-secondary-shade/10 pt-3 text-xs dark:border-white/10">
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Réduction</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">{{ $coupon->type === 'percentage' ? $coupon->value.'%' : number_format($coupon->value, 0, ',', ' ').' FCFA' }}</dd>
                </div>
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Minimum</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">{{ $coupon->min_amount ? number_format($coupon->min_amount, 0, ',', ' ').' FCFA' : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Utilisations</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">{{ $coupon->usages()->count() }}{{ $coupon->usage_limit ? ' / '.$coupon->usage_limit : '' }}</dd>
                </div>
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Expiration</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">{{ $coupon->expires_at?->format('d/m/Y') ?? '—' }}</dd>
                </div>
            </dl>
        </a>
    @empty
        <div class="bg-white px-6 py-10 text-center text-sm text-grey border border-secondary-shade/10 dark:bg-[#16201f] dark:text-white/40 dark:border-white/10">Aucun code promo pour le moment.</div>
    @endforelse
</div>

<div class="hidden overflow-x-auto bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10 lg:block">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-secondary-shade/10 text-left text-xs uppercase tracking-[0.1em] text-grey dark:border-white/10 dark:text-white/40">
                <th class="px-6 py-4 font-medium">Code</th>
                <th class="px-6 py-4 font-medium">Réduction</th>
                <th class="px-6 py-4 font-medium">Minimum</th>
                <th class="px-6 py-4 font-medium">Utilisations</th>
                <th class="px-6 py-4 font-medium">Expiration</th>
                <th class="px-6 py-4 font-medium">Statut</th>
                <th class="px-6 py-4 font-medium"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($coupons as $coupon)
                <tr onclick="window.location='{{ route('admin.coupons.edit', $coupon) }}'" class="cursor-pointer transition hover:bg-grey-tint/40 dark:hover:bg-white/5">
                    <td class="px-6 py-4 font-medium text-secondary-shade dark:text-white">{{ $coupon->code }}</td>
                    <td class="whitespace-nowrap px-6 py-4 text-secondary-shade dark:text-white">{{ $coupon->type === 'percentage' ? $coupon->value.'%' : number_format($coupon->value, 0, ',', ' ').' FCFA' }}</td>
                    <td class="whitespace-nowrap px-6 py-4 text-grey dark:text-white/50">{{ $coupon->min_amount ? number_format($coupon->min_amount, 0, ',', ' ').' FCFA' : '—' }}</td>
                    <td class="px-6 py-4 text-grey dark:text-white/50">{{ $coupon->usages()->count() }}{{ $coupon->usage_limit ? ' / '.$coupon->usage_limit : '' }}</td>
                    <td class="px-6 py-4 text-xs text-grey dark:text-white/40">{{ $coupon->expires_at?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-6 py-4">
                        <x-status-pill :tone="$coupon->is_active ? 'green' : 'neutral'" :label="$coupon->is_active ? 'Actif' : 'Désactivé'" />
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('admin.coupons.edit', $coupon) }}" title="Modifier" aria-label="Modifier" class="inline-flex h-8 w-8 items-center justify-center text-sm text-secondary-shade hover:text-primary dark:text-white/70"><i class="fa-solid fa-pen"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-6 py-10 text-center text-grey dark:text-white/40">Aucun code promo pour le moment.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6" data-pagination>{{ $coupons->links() }}</div>
