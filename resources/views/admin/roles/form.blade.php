@extends('layouts.admin')

@section('title', $role->exists ? 'Modifier le rôle' : 'Créer un rôle')

@section('content')

<a href="{{ route('admin.roles.index') }}" class="text-xs text-grey hover:text-primary dark:text-white/40"><i class="fa-solid fa-arrow-left mr-1"></i>Rôles et permissions</a>

<h1 class="mt-3 text-2xl font-semibold text-secondary-shade dark:text-white">{{ $role->exists ? 'Modifier '.$role->name : 'Créer un rôle' }}</h1>

<form action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}" method="POST" class="mt-8 max-w-3xl space-y-8">
    @csrf
    @if($role->exists) @method('PUT') @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Nom du rôle</label>
            <input type="text" name="name" value="{{ old('name', $role->name) }}" required @if($role->is_system) readonly @endif class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 disabled:opacity-60 dark:border-white/10 dark:bg-white/5 dark:text-white @if($role->is_system) bg-grey-tint/40 dark:bg-white/10 @endif">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Description</label>
            <input type="text" name="description" value="{{ old('description', $role->description) }}" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
    </div>

    @if($role->slug === \App\Models\Role::SUPER_ADMIN)
        <p class="border border-secondary-shade/10 bg-grey-tint/40 px-4 py-3 text-xs text-grey dark:border-white/10 dark:bg-white/5 dark:text-white/50">
            <i class="fa-solid fa-circle-info mr-1.5"></i>Le Super Admin possède automatiquement toutes les permissions.
        </p>
    @else
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Permissions</p>
            <div class="mt-4 space-y-6">
                @foreach($permissionsByModule as $module => $permissions)
                    <div class="border border-secondary-shade/10 p-4 dark:border-white/10">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-secondary-shade dark:text-white">{{ $module }}</p>
                            <button type="button" class="text-[11px] font-semibold uppercase tracking-[0.1em] text-primary hover:underline" onclick="const boxes=this.closest('div').parentElement.querySelectorAll('input[type=checkbox]'); const allChecked=[...boxes].every(b=>b.checked); boxes.forEach(b=>b.checked=!allChecked);">
                                Tout / Aucun
                            </button>
                        </div>
                        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @foreach($permissions as $permission)
                                <label class="flex items-center gap-2 text-sm text-secondary-shade dark:text-white/80">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, old('permissions', $selectedPermissionIds)))>
                                    {{ $permission->label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="flex gap-4 pt-2">
        <a href="{{ route('admin.roles.index') }}" class="px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade transition hover:text-primary dark:text-white/70">Annuler</a>
        <button type="submit" class="bg-primary px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md">
            Enregistrer
        </button>
    </div>
</form>

@endsection
