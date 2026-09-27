@foreach($product->images->sortBy('sort_order') as $image)
    <div
        data-image-id="{{ $image->id }}"
        draggable="true"
        @dragstart="dragId = '{{ $image->id }}'; $event.dataTransfer.effectAllowed = 'move'"
        @dragover.prevent
        @drop.prevent="
            const dragged = $refs.grid.querySelector(`[data-image-id='${dragId}']`);
            if (dragged && dragged !== $el) { $el.before(dragged); persist(); }
        "
        class="group relative aspect-square cursor-move overflow-hidden bg-grey-tint dark:bg-white/10"
    >
        <img src="{{ img_url($image->url, 300, 300) }}" alt="{{ $image->alt }}" class="pointer-events-none h-full w-full object-cover">

        @if($loop->first)
            <span class="pointer-events-none absolute left-1 top-1 z-10 flex h-5 w-5 items-center justify-center bg-secondary-shade text-white" title="Photo principale" aria-label="Photo principale">
                <i class="fa-solid fa-star text-[9px]"></i>
            </span>
        @endif

        <div class="absolute inset-0 flex items-center justify-center gap-3 bg-secondary-shade/60 opacity-0 transition group-hover:opacity-100">
            @unless($loop->first)
                <form action="{{ route('admin.products.images.primary', [$product, $image]) }}" method="POST" @submit.prevent="setPrimary($event)">
                    @csrf
                    <button type="submit" class="text-white" title="Définir comme photo principale" aria-label="Définir comme photo principale">
                        <i class="fa-regular fa-star"></i>
                    </button>
                </form>
            @endunless
            <form action="{{ route('admin.products.images.destroy', [$product, $image]) }}" method="POST" @submit.prevent="destroyImage($event)">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-white" title="Supprimer" aria-label="Supprimer">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </form>
        </div>
    </div>
@endforeach

<div
    @dragover.prevent
    @drop.prevent="
        const dragged = $refs.grid.querySelector(`[data-image-id='${dragId}']`);
        if (dragged) { $refs.grid.appendChild(dragged); persist(); }
    "
>
    <form action="{{ route('admin.products.images.store', $product) }}" method="POST" enctype="multipart/form-data" @submit.prevent="upload($event)">
        @csrf
        <x-image-dropzone name="images[]" :auto-submit="true" :compact="true" />
    </form>
</div>
