@extends('layouts.admin')

@section('title', 'Attributs')

@section('content')

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Attributs</h1>
</div>
<p class="mt-2 max-w-2xl text-sm text-grey dark:text-white/40">
    Les attributs définissent les caractéristiques que peuvent avoir vos produits (Couleur, Puissance, Longueur…).
    Associez-les ensuite à une catégorie depuis sa fiche pour qu'ils apparaissent lors de la création d'un produit
    de cette catégorie.
</p>

{{-- Nouvel attribut --}}
<div class="mt-8 bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
    <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Nouvel attribut</h2>
    <form action="{{ route('admin.attributes.store') }}" method="POST" class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end">
        @csrf
        <div class="flex-1">
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Nom</label>
            <input type="text" name="name" required placeholder="Ex. Puissance, Longueur, Température…" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
        </div>
        <div class="sm:w-52">
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Type</label>
            <select name="type" class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                <option value="select">Liste (ex. 7W, 12W…)</option>
                <option value="color">Couleur (pastilles)</option>
            </select>
        </div>
        <button type="submit" class="whitespace-nowrap bg-primary px-6 py-2.5 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade hover:shadow-md">
            Ajouter
        </button>
    </form>
</div>

{{-- Liste des attributs --}}
<div class="mt-6 space-y-4">
    @forelse($attributes as $attribute)
        <div class="bg-white p-6 border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="font-medium text-secondary-shade dark:text-white">{{ $attribute->name }}</span>
                    <span class="bg-grey-tint px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.1em] text-grey dark:bg-white/10 dark:text-white/50">
                        {{ $attribute->type === 'color' ? 'Couleur' : 'Liste' }}
                    </span>
                </div>
                <form action="{{ route('admin.attributes.destroy', $attribute) }}" method="POST" onsubmit="return confirm('Supprimer cet attribut et toutes ses valeurs ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-red-600 hover:underline dark:text-red-400">Supprimer</button>
                </form>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                @forelse($attribute->values as $value)
                    <span class="group inline-flex items-center gap-2 border border-secondary-shade/15 py-1.5 pl-3 pr-1.5 text-xs text-secondary-shade dark:border-white/10 dark:text-white">
                        @if($attribute->type === 'color')
                            <span class="h-3.5 w-3.5 shrink-0 rounded-full ring-1 ring-secondary-shade/15 dark:ring-white/20" style="background-color: {{ $value->color_code }}"></span>
                        @endif
                        {{ $value->value }}
                        <form action="{{ route('admin.attributes.values.destroy', [$attribute, $value]) }}" method="POST" onsubmit="return confirm('Supprimer cette valeur ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="flex h-5 w-5 items-center justify-center text-secondary-shade/40 transition hover:text-red-600 dark:text-white/30 dark:hover:text-red-400" aria-label="Supprimer">
                                <i class="fa-solid fa-xmark text-[10px]"></i>
                            </button>
                        </form>
                    </span>
                @empty
                    <p class="text-xs text-grey dark:text-white/40">Aucune valeur pour le moment.</p>
                @endforelse
            </div>

            <form
                action="{{ route('admin.attributes.values.store', $attribute) }}"
                method="POST"
                class="mt-4 flex flex-wrap items-center gap-3 border-t border-secondary-shade/10 pt-4 dark:border-white/10"
            >
                @csrf
                @if($attribute->type === 'color')
                    <input type="color" name="color_code" value="#263F3A" class="h-9 w-11 shrink-0 cursor-pointer border border-secondary-shade/15 bg-transparent p-0.5 dark:border-white/10">
                @endif
                <input
                    type="text"
                    name="value"
                    required
                    placeholder="{{ $attribute->type === 'color' ? 'Nom de la couleur (ex. Blanc)' : 'Nouvelle valeur (ex. 12W)' }}"
                    class="min-w-[180px] flex-1 border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white"
                >
                <button type="submit" class="whitespace-nowrap border border-secondary-shade/20 px-4 py-2 text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade transition hover:border-secondary-shade dark:border-white/15 dark:text-white/70">
                    <i class="fa-solid fa-plus mr-1.5 text-[10px]"></i>Ajouter une valeur
                </button>
            </form>
        </div>
    @empty
        <div class="bg-white px-6 py-14 text-center text-sm text-grey border border-secondary-shade/10 dark:bg-[#16201f] dark:text-white/40 dark:border-white/10">
            Aucun attribut pour le moment — créez-en un ci-dessus.
        </div>
    @endforelse
</div>

@endsection
