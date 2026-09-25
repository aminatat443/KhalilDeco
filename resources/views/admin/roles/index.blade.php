@extends('layouts.admin')

@section('title', 'Rôles et permissions')

@section('content')

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Rôles et permissions</h1>
        <p class="mt-1 text-sm text-grey dark:text-white/40">Modifier les permissions d'un rôle affecte immédiatement tous les utilisateurs qui le portent.</p>
    </div>
    @can('create', App\Models\Role::class)
        <a href="{{ route('admin.roles.create') }}" class="block bg-primary px-6 py-3 text-center text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md sm:inline-block">
            Créer un rôle
        </a>
    @endcan
</div>

<div class="mt-8 space-y-3 lg:hidden">
    @foreach($roles as $role)
        <div class="border border-secondary-shade/10 bg-white p-4 dark:border-white/10 dark:bg-[#16201f]">
            <div class="flex items-center justify-between gap-3">
                <p class="font-medium text-secondary-shade dark:text-white">{{ $role->name }}</p>
                @if($role->is_system)
                    <span class="shrink-0 px-2 py-0.5 text-[11px] font-semibold bg-grey-tint text-grey dark:bg-white/10 dark:text-white/50">Système</span>
                @endif
            </div>
            @if($role->description)
                <p class="mt-1 text-xs text-grey dark:text-white/40">{{ $role->description }}</p>
            @endif
            <dl class="mt-3 grid grid-cols-2 gap-2 border-t border-secondary-shade/10 pt-3 text-xs dark:border-white/10">
                <div><dt class="text-grey/60 dark:text-white/30">Utilisateurs</dt><dd class="mt-0.5 text-secondary-shade dark:text-white">{{ $role->users_count }}</dd></div>
                <div><dt class="text-grey/60 dark:text-white/30">Permissions</dt><dd class="mt-0.5 text-secondary-shade dark:text-white">{{ $role->permissions_count }}</dd></div>
            </dl>
            <div class="mt-3 flex items-center justify-end gap-4 border-t border-secondary-shade/10 pt-3 dark:border-white/10">
                @can('update', $role)
                    <a href="{{ route('admin.roles.edit', $role) }}" class="text-xs text-grey/70 hover:text-primary dark:text-white/40"><i class="fa-solid fa-pen mr-1"></i>Modifier</a>
                @endcan
                @can('delete', $role)
                    <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Supprimer le rôle « {{ addslashes($role->name) }} » ?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-grey/70 hover:text-red-600 dark:text-white/40 dark:hover:text-red-400"><i class="fa-solid fa-trash mr-1"></i>Supprimer</button>
                    </form>
                @endcan
            </div>
        </div>
    @endforeach
</div>

<div class="mt-8 hidden overflow-x-auto bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10 lg:block">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-secondary-shade/10 text-left text-xs uppercase tracking-[0.1em] text-grey dark:border-white/10 dark:text-white/40">
                <th class="px-6 py-4 font-medium">Nom</th>
                <th class="px-6 py-4 font-medium">Utilisateurs</th>
                <th class="px-6 py-4 font-medium">Permissions</th>
                <th class="px-6 py-4 font-medium">Statut</th>
                <th class="px-6 py-4 font-medium"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
            @foreach($roles as $role)
                <tr class="transition hover:bg-grey-tint/40 dark:hover:bg-white/5">
                    <td class="px-6 py-4">
                        <p class="font-medium text-secondary-shade dark:text-white">{{ $role->name }}</p>
                        @if($role->description)
                            <p class="text-xs text-grey dark:text-white/40">{{ $role->description }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-secondary-shade dark:text-white">{{ $role->users_count }}</td>
                    <td class="px-6 py-4 text-secondary-shade dark:text-white">{{ $role->permissions_count }}</td>
                    <td class="px-6 py-4">
                        @if($role->is_system)
                            <span class="px-2 py-0.5 text-[11px] font-semibold bg-grey-tint text-grey dark:bg-white/10 dark:text-white/50">Système</span>
                        @else
                            <span class="px-2 py-0.5 text-[11px] font-semibold bg-primary-tint text-primary-shade dark:bg-primary/15 dark:text-primary">Personnalisé</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-end gap-3">
                            @can('update', $role)
                                <a href="{{ route('admin.roles.edit', $role) }}" class="text-xs text-grey/70 hover:text-primary dark:text-white/40" aria-label="Modifier"><i class="fa-solid fa-pen"></i></a>
                            @endcan
                            @can('delete', $role)
                                <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Supprimer le rôle « {{ addslashes($role->name) }} » ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-grey/70 hover:text-red-600 dark:text-white/40 dark:hover:text-red-400" aria-label="Supprimer"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection
