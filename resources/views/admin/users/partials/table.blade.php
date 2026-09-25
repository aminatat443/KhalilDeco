@php
    $roleLabels = ['client' => 'Client', 'gestionnaire' => 'Gestionnaire', 'admin' => 'Administrateur', 'super_admin' => 'Super admin'];
    $roleTones = [
        'client' => 'bg-grey-tint text-grey dark:bg-white/10 dark:text-white/50',
        'gestionnaire' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400',
        'admin' => 'bg-primary-tint text-primary-shade dark:bg-primary/15 dark:text-primary',
        'super_admin' => 'bg-secondary-shade text-white dark:bg-white dark:text-secondary-shade',
    ];

    $avatarOf = fn ($user) => \Illuminate\Support\Str::of($user->name)->explode(' ')->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->join('');
    $roleBadge = function ($user) use ($roleLabels, $roleTones) {
        if ($user->roleModel) {
            $tone = $user->roleModel->slug === 'super-admin'
                ? $roleTones['super_admin']
                : ($user->roleModel->slug === 'administrateur' ? $roleTones['admin'] : $roleTones['gestionnaire']);
            return ['label' => $user->roleModel->name, 'tone' => $tone];
        }
        return ['label' => $roleLabels[$user->role->value] ?? $user->role->value, 'tone' => $roleTones[$user->role->value] ?? $roleTones['client']];
    };
@endphp

<p class="mb-4 text-xs text-grey dark:text-white/40">{{ $users->total() }} {{ Str::plural('compte', $users->total()) }}</p>

<div class="space-y-3 lg:hidden">
    @forelse($users as $user)
        <div class="bg-white p-4 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center bg-secondary-shade text-xs font-bold uppercase text-white">
                    {{ $avatarOf($user) }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium text-secondary-shade dark:text-white">{{ $user->name }}</p>
                    <p class="truncate text-xs text-grey dark:text-white/50">{{ $user->email }}</p>
                </div>
                @if($user->isOnline())
                    <span class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-green-600 dark:text-green-400">
                        <span class="h-2 w-2 rounded-full bg-green-500"></span>En ligne
                    </span>
                @else
                    <span class="inline-flex shrink-0 items-center gap-1.5 text-xs text-grey dark:text-white/40">
                        <span class="h-2 w-2 rounded-full bg-grey/40 dark:bg-white/20"></span>Hors ligne
                    </span>
                @endif
            </div>
            <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2.5 border-t border-secondary-shade/10 pt-3 text-xs dark:border-white/10">
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Rôle</dt>
                    <dd class="mt-1">
                        @php($badge = $roleBadge($user))
                        <span class="inline-flex px-2 py-0.5 text-[11px] font-semibold {{ $badge['tone'] }}">
                            {{ $badge['label'] }}
                        </span>
                        @if(! $user->is_active)
                            <span class="ml-1 inline-flex px-2 py-0.5 text-[11px] font-semibold bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400">Désactivé</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-grey/60 dark:text-white/30">Compte créé</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">{{ $user->created_at->format('d/m/Y') }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-grey/60 dark:text-white/30">Dernière connexion</dt>
                    <dd class="mt-0.5 text-secondary-shade dark:text-white">
                        {{ $user->last_login_at?->diffForHumans() ?? '—' }}
                        @if(! $user->isOnline() && $user->last_activity)
                            <span class="block text-grey/70 dark:text-white/30">Hors ligne depuis {{ \Illuminate\Support\Carbon::createFromTimestamp($user->last_activity)->diffForHumans() }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
            <div class="mt-3 flex items-center justify-end gap-4 border-t border-secondary-shade/10 pt-3 dark:border-white/10">
                @can('update', $user)
                    <a href="{{ route('admin.users.edit', $user) }}" class="text-xs text-grey/70 hover:text-primary dark:text-white/40">
                        <i class="fa-solid fa-pen mr-1"></i>Modifier
                    </a>
                @endcan
                @can('delete', $user)
                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Supprimer définitivement le compte « {{ addslashes($user->name) }} » ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs text-grey/70 hover:text-red-600 dark:text-white/40 dark:hover:text-red-400">
                            <i class="fa-solid fa-trash mr-1"></i>Supprimer
                        </button>
                    </form>
                @endcan
            </div>
        </div>
    @empty
        <div class="flex flex-col items-center gap-3 border border-secondary-shade/10 bg-white px-6 py-14 text-center dark:border-white/10 dark:bg-[#16201f]">
            <span class="flex h-11 w-11 items-center justify-center border border-secondary-shade/10 text-grey/50 dark:border-white/10 dark:text-white/30"><i class="fa-solid fa-user-group"></i></span>
            <p class="text-sm text-grey dark:text-white/40">Aucun compte ne correspond à ces critères.</p>
        </div>
    @endforelse
</div>

<div class="hidden overflow-x-auto bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10 lg:block">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-secondary-shade/10 text-left text-xs uppercase tracking-[0.1em] text-grey dark:border-white/10 dark:text-white/40">
                <th class="px-6 py-4 font-medium">Utilisateur</th>
                <th class="px-6 py-4 font-medium">Rôle</th>
                <th class="px-6 py-4 font-medium">Statut</th>
                <th class="px-6 py-4 font-medium">Dernière connexion</th>
                <th class="px-6 py-4 font-medium">Compte créé</th>
                <th class="px-6 py-4 font-medium"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
            @forelse($users as $user)
                <tr class="transition hover:bg-grey-tint/40 dark:hover:bg-white/5">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center bg-secondary-shade text-xs font-bold uppercase text-white">
                                {{ $avatarOf($user) }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate font-medium text-secondary-shade dark:text-white">{{ $user->name }}</p>
                                <p class="truncate text-xs text-grey dark:text-white/50">{{ $user->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @php($badge = $roleBadge($user))
                        <span class="inline-flex whitespace-nowrap px-2.5 py-1 text-[11px] font-semibold {{ $badge['tone'] }}">
                            {{ $badge['label'] }}
                        </span>
                        @if(! $user->is_active)
                            <span class="ml-1 inline-flex whitespace-nowrap px-2.5 py-1 text-[11px] font-semibold bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400">Désactivé</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($user->isOnline())
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-green-600 dark:text-green-400">
                                <span class="h-2 w-2 rounded-full bg-green-500"></span>
                                En ligne
                            </span>
                        @elseif($user->last_activity)
                            <span class="inline-flex items-center gap-1.5 text-xs text-grey dark:text-white/40">
                                <span class="h-2 w-2 rounded-full bg-grey/40 dark:bg-white/20"></span>
                                Hors ligne depuis {{ \Illuminate\Support\Carbon::createFromTimestamp($user->last_activity)->diffForHumans() }}
                            </span>
                        @elseif($user->last_login_at)
                            <span class="inline-flex items-center gap-1.5 text-xs text-grey dark:text-white/40">
                                <span class="h-2 w-2 rounded-full bg-grey/40 dark:bg-white/20"></span>
                                Hors ligne
                            </span>
                        @else
                            <span class="text-xs text-grey/60 dark:text-white/30">Jamais connecté</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-xs text-grey dark:text-white/40">
                        {{ $user->last_login_at?->diffForHumans() ?? '—' }}
                    </td>
                    <td class="px-6 py-4 text-xs text-grey dark:text-white/40">{{ $user->created_at->format('d/m/Y') }}</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-end gap-3">
                            @can('update', $user)
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-xs text-grey/70 hover:text-primary dark:text-white/40" aria-label="Modifier">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                            @endcan
                            @can('delete', $user)
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Supprimer définitivement le compte « {{ addslashes($user->name) }} » ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-grey/70 hover:text-red-600 dark:text-white/40 dark:hover:text-red-400" aria-label="Supprimer">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-6 py-14">
                    <div class="flex flex-col items-center gap-3 text-center">
                        <span class="flex h-11 w-11 items-center justify-center border border-secondary-shade/10 text-grey/50 dark:border-white/10 dark:text-white/30"><i class="fa-solid fa-user-group"></i></span>
                        <p class="text-sm text-grey dark:text-white/40">Aucun compte ne correspond à ces critères.</p>
                    </div>
                </td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6" data-pagination>{{ $users->links() }}</div>
