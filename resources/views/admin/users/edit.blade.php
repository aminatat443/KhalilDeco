@extends('layouts.admin-modal')

@php($modalBack = route('admin.users.index'))

@section('title', 'Modifier le compte')

@section('modal')

<h1 class="pr-10 text-2xl font-semibold text-secondary-shade dark:text-white">Modifier {{ $targetUser->name }}</h1>
<p class="mt-2 text-sm text-grey dark:text-white/40">Le rôle attribué ici détermine les permissions du compte dans l'administration.</p>

<form action="{{ route('admin.users.update', $targetUser) }}" method="POST" class="mt-8 space-y-6">
    @csrf
    @method('PUT')

    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Nom complet</label>
        <input type="text" name="name" value="{{ old('name', $targetUser->name) }}" required class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Email</label>
            <input type="email" name="email" value="{{ old('email', $targetUser->email) }}" required class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Téléphone</label>
            <input type="text" name="phone" value="{{ old('phone', $targetUser->phone) }}" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
    </div>

    @if($targetUser->id === auth()->id())
        <p class="border border-secondary-shade/10 bg-grey-tint/40 px-4 py-3 text-xs text-grey dark:border-white/10 dark:bg-white/5 dark:text-white/50">
            <i class="fa-solid fa-circle-info mr-1.5"></i>Vous ne pouvez pas modifier votre propre rôle ou statut.
        </p>
        <input type="hidden" name="role_id" value="{{ $targetUser->role_id }}">
        <input type="hidden" name="is_active" value="{{ $targetUser->is_active ? 1 : 0 }}">
    @else
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Rôle</label>
            <select name="role_id" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                <option value="">Aucun rôle RBAC (accès de base)</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" @selected(old('role_id', $targetUser->role_id) == $role->id)>{{ $role->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Statut</label>
            <select name="is_active" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                <option value="1" @selected(old('is_active', $targetUser->is_active ? '1' : '0') === '1')>Actif</option>
                <option value="0" @selected(old('is_active', $targetUser->is_active ? '1' : '0') === '0')>Désactivé</option>
            </select>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Nouveau mot de passe</label>
            <input type="password" name="password" placeholder="Laisser vide pour ne pas changer" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Confirmer</label>
            <input type="password" name="password_confirmation" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
    </div>

    <div class="flex gap-4 pt-2">
        <a href="{{ route('admin.users.index') }}" class="px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade transition hover:text-primary dark:text-white/70">Annuler</a>
        <button type="submit" class="bg-primary px-8 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md">
            Enregistrer
        </button>
    </div>
</form>

@endsection
