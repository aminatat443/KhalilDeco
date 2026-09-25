@props(['icon', 'label', 'value' => null, 'trend' => null, 'href' => null, 'available' => true, 'sub' => null])

@php
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif class="flex items-center gap-4 border border-secondary-shade/10 bg-white p-4 transition dark:border-white/10 dark:bg-[#16201f] sm:p-6 {{ $href ? 'hover:border-primary/40' : '' }}">
    <span class="flex h-10 w-10 shrink-0 items-center justify-center border border-secondary-shade/10 text-secondary-shade dark:border-white/10 dark:text-white/70">
        <i class="fa-solid {{ $icon }} text-sm"></i>
    </span>
    <div class="min-w-0 flex-1">
        <p class="truncate text-xs font-semibold uppercase tracking-[0.12em] text-grey dark:text-white/40">{{ $label }}</p>
        @if($available)
            <div class="mt-1 flex flex-wrap items-baseline gap-x-2">
                <p class="text-lg font-semibold text-secondary-shade dark:text-white sm:text-2xl">{{ $value }}</p>
                @if($trend !== null)
                    <span class="inline-flex shrink-0 items-center gap-1 text-[11px] font-semibold {{ $trend >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        <i class="fa-solid {{ $trend >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                        {{ abs($trend) }}%
                    </span>
                @endif
            </div>
            @if($sub)
                <p class="mt-0.5 truncate text-[11px] text-grey dark:text-white/40">{{ $sub }}</p>
            @endif
        @else
            <p class="mt-1 text-sm italic text-grey/60 dark:text-white/30">Donnée non disponible</p>
        @endif
    </div>
</{{ $tag }}>
