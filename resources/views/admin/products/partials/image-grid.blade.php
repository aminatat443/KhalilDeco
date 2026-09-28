@php
    // Accepte soit $images explicite (passé par admin-image-manager.blade.php, cas normal —
    // $product/$variant y sont toujours définis aussi, pour construire les routes), soit une
    // galerie produit générique par défaut (réponses AJAX de ProductImageController qui ne
    // repassent que $images déjà scopée, $product/$variant restent disponibles dans ce cas-là).
    $images ??= $product->images;
@endphp

@foreach($images->sortBy('sort_order') as $image)
    <div
        data-image-id="{{ $image->id }}"
        class="relative aspect-square overflow-hidden bg-grey-tint dark:bg-white/10"
    >
        <img
            src="{{ img_url($image->url, 300, 300) }}"
            alt="{{ $image->alt }}"
            @click="previewUrl = '{{ $image->url }}'"
            class="h-full w-full cursor-zoom-in object-cover"
        >

        @if($loop->first)
            <span class="pointer-events-none absolute left-1 top-1 z-10 flex h-5 w-5 items-center justify-center bg-secondary-shade text-white" title="Photo principale" aria-label="Photo principale">
                <i class="fa-solid fa-star text-[9px]"></i>
            </span>
        @endif

        {{-- Poignée de glisser-déposer : geste dédié (Pointer Events, souris ET tactile) plutôt
             que toute la tuile, pour ne pas intercepter le défilement de la page au doigt sur
             mobile/tablette (touch-none uniquement ici, jamais sur la tuile entière). --}}
        <button
            type="button"
            @pointerdown="startDrag($event, '{{ $image->id }}')"
            class="absolute right-1 top-1 z-10 flex h-6 w-6 touch-none cursor-grab items-center justify-center bg-secondary-shade/70 text-white active:cursor-grabbing"
            title="Glisser pour réordonner"
            aria-label="Glisser pour réordonner"
        >
            <i class="fa-solid fa-grip-vertical text-[10px]"></i>
        </button>

        {{-- Étoile "principale" et suppression : toujours visibles (pas seulement au survol —
             inatteignable au doigt sur tablette/mobile), tailles pensées pour rester tapables. --}}
        <div class="absolute inset-x-0 bottom-0 z-10 flex items-center gap-1 p-1">
            @unless($loop->first)
                <form action="{{ $variant ? route('admin.products.variants.images.primary', [$product, $variant, $image]) : route('admin.products.images.primary', [$product, $image]) }}" method="POST" @submit.prevent="setPrimary($event)">
                    @csrf
                    <button type="submit" class="flex h-6 w-6 items-center justify-center bg-secondary-shade/70 text-white" title="Définir comme photo principale" aria-label="Définir comme photo principale">
                        <i class="fa-regular fa-star text-[10px]"></i>
                    </button>
                </form>
            @endunless
            <form action="{{ $variant ? route('admin.products.variants.images.destroy', [$product, $variant, $image]) : route('admin.products.images.destroy', [$product, $image]) }}" method="POST" class="ml-auto" @submit.prevent="destroyImage($event)">
                @csrf
                @method('DELETE')
                <button type="submit" class="flex h-6 w-6 items-center justify-center bg-secondary-shade/70 text-white" title="Supprimer" aria-label="Supprimer">
                    <i class="fa-solid fa-trash text-[10px]"></i>
                </button>
            </form>
        </div>
    </div>
@endforeach

{{-- Marqueur de fin de grille : le glisser-déposer par pointeur y dépose l'image en dernière
     position (avant, l'ancien drop HTML5 natif l'ajoutait après cette tuile "+", ce qui la faisait
     visuellement apparaître après elle). --}}
<div data-drop-end>
    <form action="{{ $variant ? route('admin.products.variants.images.store', [$product, $variant]) : route('admin.products.images.store', $product) }}" method="POST" enctype="multipart/form-data" @submit.prevent="upload($event)">
        @csrf
        <x-image-dropzone name="images[]" :auto-submit="true" :compact="true" />
    </form>
</div>
