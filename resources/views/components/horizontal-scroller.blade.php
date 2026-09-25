@props(['gap' => 'gap-5'])

<div
    x-data="{
        canLeft: false,
        canRight: false,
        check() {
            const el = this.$refs.track;
            if (! el) return;
            this.canLeft = el.scrollLeft > 4;
            this.canRight = el.scrollLeft < el.scrollWidth - el.clientWidth - 4;
        },
        scroll(dir) {
            this.$refs.track.scrollBy({ left: dir * this.$refs.track.clientWidth * 0.85, behavior: 'smooth' });
        },
    }"
    x-init="$nextTick(() => check()); window.addEventListener('resize', () => check())"
    class="relative min-w-0"
>
    <button
        type="button"
        x-show="canLeft"
        x-cloak
        @click="scroll(-1)"
        class="absolute -left-4 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center bg-white text-secondary-shade shadow-[0_8px_24px_-8px_rgba(33,55,55,0.3)] transition hover:text-primary sm:-left-5"
        aria-label="Voir précédent"
    >
        <i class="fa-solid fa-chevron-left text-xs"></i>
    </button>

    <div
        x-ref="track"
        @scroll.passive="check()"
        class="flex {{ $gap }} overflow-x-auto scroll-smooth pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
    >
        {{ $slot }}
    </div>

    <button
        type="button"
        x-show="canRight"
        x-cloak
        @click="scroll(1)"
        class="absolute -right-4 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center bg-white text-secondary-shade shadow-[0_8px_24px_-8px_rgba(33,55,55,0.3)] transition hover:text-primary sm:-right-5"
        aria-label="Voir suivant"
    >
        <i class="fa-solid fa-chevron-right text-xs"></i>
    </button>
</div>
