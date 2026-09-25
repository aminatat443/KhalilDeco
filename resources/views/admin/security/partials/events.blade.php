<div class="space-y-3 lg:hidden">
    @forelse($events as $event)
        <div class="bg-white p-4 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
            <div class="flex items-center justify-between gap-3">
                <x-status-pill :tone="\App\Models\SecurityEvent::SEVERITY_TONES[$event->severity] ?? 'neutral'" :label="\App\Models\SecurityEvent::TYPES[$event->type] ?? $event->type" />
                <span class="shrink-0 text-xs text-grey dark:text-white/40">{{ $event->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <dl class="mt-3 border-t border-secondary-shade/10 pt-3 text-xs dark:border-white/10">
                <dt class="text-grey/60 dark:text-white/30">IP</dt>
                <dd class="mt-0.5 font-mono text-secondary-shade dark:text-white">{{ $event->ip_address }}</dd>
                <dt class="mt-2 text-grey/60 dark:text-white/30">Détail</dt>
                <dd class="mt-0.5 text-secondary-shade dark:text-white/80">{{ $event->message }}</dd>
            </dl>
        </div>
    @empty
        <div class="bg-white px-6 py-10 text-center text-sm text-grey border border-secondary-shade/10 dark:bg-[#16201f] dark:text-white/40 dark:border-white/10">Aucun événement pour le moment.</div>
    @endforelse
</div>

<div class="hidden overflow-x-auto lg:block">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-secondary-shade/10 text-left text-xs uppercase tracking-[0.1em] text-grey dark:border-white/10 dark:text-white/40">
                <th class="py-3 pr-4 font-medium">Date</th>
                <th class="py-3 pr-4 font-medium">Type</th>
                <th class="py-3 pr-4 font-medium">IP</th>
                <th class="py-3 pr-4 font-medium">Détail</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($events as $event)
                <tr>
                    <td class="whitespace-nowrap py-3 pr-4 text-xs text-grey dark:text-white/40">{{ $event->created_at->format('d/m/Y H:i') }}</td>
                    <td class="py-3 pr-4">
                        <x-status-pill :tone="\App\Models\SecurityEvent::SEVERITY_TONES[$event->severity] ?? 'neutral'" :label="\App\Models\SecurityEvent::TYPES[$event->type] ?? $event->type" />
                    </td>
                    <td class="py-3 pr-4 font-mono text-xs text-secondary-shade dark:text-white">{{ $event->ip_address }}</td>
                    <td class="py-3 pr-4 text-secondary-shade dark:text-white/80">{{ $event->message }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-10 text-center text-grey dark:text-white/40">Aucun événement pour le moment.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6" data-pagination>{{ $events->links() }}</div>
