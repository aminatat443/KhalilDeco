<header
    x-data="{ scrolled: false, searchOpen: false, favoritesOpen: false, mobileMenuOpen: false }"
    x-init="
        $store.cart.hydrate({{ Illuminate\Support\Js::from($cartSummary ?? ['items' => [], 'count' => 0, 'subtotal' => 0]) }});
        $store.favorites.hydrate({{ Illuminate\Support\Js::from($favoritesSummary ?? null) }});
        @if(request()->boolean('login')) $store.ui.openLogin(); @endif
        @if(request()->boolean('favoris')) favoritesOpen = true; @endif
    "
    @scroll.window="scrolled = window.scrollY > 12"
    @favorite-added.window="favoritesOpen = true"
    class="sticky top-0 z-50 bg-white"
>

    {{-- Topbar --}}
    <div class="border-b border-secondary-shade/10 px-4 py-1.5 text-center text-[9px] font-medium uppercase tracking-[0.08em] text-grey sm:text-[11px] sm:tracking-[0.2em]">
        Livraison partout au Sénégal — Décoration, faux plafonds & quincaillerie
    </div>


    {{-- Navigation principale --}}
    <div
        x-data="{ open: null }"
        @mouseleave="open = null"
        :class="scrolled ? 'h-20 border-secondary-shade/10 shadow-[0_1px_0_0_rgba(33,55,55,0.06)]' : 'h-24 border-transparent'"
        class="relative border-b bg-white transition-[height] duration-300"
    >
        <div class="mx-auto grid h-full max-w-[1600px] grid-cols-[auto_1fr_auto] items-center gap-4 px-6 sm:px-10 xl:flex xl:gap-10">

            {{-- Bouton menu mobile --}}
            <button type="button" @click="mobileMenuOpen = true" class="text-secondary-shade transition hover:text-primary xl:hidden" aria-label="Menu">
                <i class="fa-solid fa-bars text-[19px]"></i>
            </button>

            {{-- Logo : centré dans l'espace disponible entre le menu et les actions sur mobile/tablette
                 (grille, jamais de chevauchement possible), aligné à gauche dans le flux normal à partir de xl --}}
            <a href="{{ route('home') }}" class="col-start-2 shrink-0 justify-self-center xl:justify-self-auto">
                <img src="{{ asset('images/logo_khalil_deco_full.png') }}" alt="Khalil Déco" class="h-14 w-auto sm:h-16">
            </a>


            {{-- Navigation catégories --}}
            <nav class="hidden h-full flex-1 items-center justify-center gap-5 xl:flex 2xl:gap-8">
                @foreach($navCategories ?? [] as $universe)
                    <div class="relative flex h-full items-center" @mouseenter="open = {{ $universe->id }}">
                        <a
                            href="{{ route('catalog.show', $universe) }}"
                            :class="open === {{ $universe->id }} ? 'text-primary' : 'text-secondary-shade'"
                            class="whitespace-nowrap text-[13px] font-medium uppercase tracking-[0.12em] transition hover:text-primary"
                        >
                            {{ $universe->name }}
                        </a>

                        @if($universe->children->isNotEmpty())
                            <div
                                x-show="open === {{ $universe->id }}"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                x-cloak
                                class="absolute left-0 top-full z-30 w-max max-w-md border border-secondary-shade/10 bg-white shadow-[0_24px_40px_-24px_rgba(33,55,55,0.18)]"
                            >
                                <div class="grid grid-cols-[1fr_auto] gap-5 px-5 py-5">

                                    <div class="columns-2 gap-x-5">
                                        @foreach($universe->children as $child)
                                            <a
                                                href="{{ route('catalog.show', $child) }}"
                                                class="block break-inside-avoid py-1.5 text-[13px] text-grey transition hover:text-primary"
                                            >
                                                {{ $child->name }}
                                            </a>
                                        @endforeach
                                    </div>

                                    <div class="w-32 shrink-0 border-l border-secondary-shade/10 pl-5">
                                        <p class="font-display text-xl italic text-secondary-shade">{{ $universe->name }}</p>
                                        <a
                                            href="{{ route('catalog.show', $universe) }}"
                                            class="group mt-3 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade transition hover:text-primary"
                                        >
                                            Voir tout
                                            <i class="fa-solid fa-arrow-right text-[10px] transition group-hover:translate-x-1"></i>
                                        </a>
                                    </div>

                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach

                <a
                    href="{{ route('search', ['promotions' => 1]) }}"
                    class="text-[13px] font-semibold uppercase tracking-[0.12em] text-primary transition hover:text-primary-shade"
                >
                    Promo
                </a>
            </nav>


            {{-- Actions --}}
            <div class="ml-auto flex shrink-0 items-center gap-6" @mouseenter="open = null">

                <button type="button" @click="searchOpen = !searchOpen; open = null" class="text-secondary-shade transition hover:text-primary" aria-label="Rechercher">
                    <i class="fa-solid fa-fw text-[17px]" :class="searchOpen ? 'fa-xmark' : 'fa-magnifying-glass'"></i>
                </button>

                @guest
                    <button type="button" @click="$store.ui.openLogin()" class="cursor-pointer text-secondary-shade transition hover:text-primary xl:hidden" aria-label="Se connecter">
                        <i class="fa-regular fa-user text-[17px]"></i>
                    </button>
                @endguest

                @auth
                    <x-notification-bell />

                    <div class="relative hidden xl:block" x-data="{ accountOpen: false }" @mouseenter="accountOpen = true" @mouseleave="accountOpen = false" @click.outside="accountOpen = false">
                        <button type="button" class="flex items-center gap-1.5 text-secondary-shade transition hover:text-primary" aria-label="Compte">
                            <i class="fa-solid fa-user text-[17px]"></i>
                            <i class="fa-solid fa-chevron-down text-[9px] transition-transform duration-200" :class="accountOpen ? 'rotate-180' : ''"></i>
                        </button>

                        {{-- pt-4 (plutôt qu'un mt-4 sur la boîte blanche) pour que la zone de survol
                             reste continue entre le bouton et le menu — sinon la souris "sort" du
                             survol en traversant l'espace et le menu se ferme avant d'être atteint. --}}
                        <div
                            x-show="accountOpen"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-cloak
                            class="absolute right-0 top-full z-30 pt-4"
                        >
                            <div class="w-52 border border-secondary-shade/10 bg-white py-3 shadow-[0_24px_40px_-24px_rgba(33,55,55,0.18)]">
                                <p class="px-5 pb-2 text-xs text-grey">Connecté en tant que</p>
                                <p class="px-5 pb-3 text-sm font-medium text-secondary-shade">{{ auth()->user()->name }}</p>

                                <a href="{{ route('account.index') }}" class="block border-t border-secondary-shade/10 px-5 py-2.5 text-sm text-secondary-shade transition hover:text-primary">
                                    Mon profil
                                </a>

                                {{-- Le staff (Gestionnaire/Admin/Super Admin) n'achète pas sur la boutique :
                                     pas de "Mes commandes" ni de favoris pour ces rôles. --}}
                                @unless(auth()->user()->isStaffMember())
                                    <a href="{{ route('account.orders') }}" class="block px-5 py-2.5 text-sm text-secondary-shade transition hover:text-primary">
                                        Mes commandes
                                    </a>
                                    <a href="{{ route('account.returns') }}" class="block px-5 py-2.5 text-sm text-secondary-shade transition hover:text-primary">
                                        Mes retours
                                    </a>
                                @else
                                    <a href="{{ route('admin.dashboard') }}" class="block px-5 py-2.5 text-sm text-secondary-shade transition hover:text-primary">
                                        Back-office
                                    </a>
                                @endunless

                                <form action="{{ route('logout') }}" method="POST" class="border-t border-secondary-shade/10 pt-2">
                                    @csrf
                                    <button type="submit" class="block w-full px-5 py-2 text-left text-sm text-secondary-shade transition hover:text-primary">
                                        Se déconnecter
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <button type="button" @click="$store.ui.openLogin()" class="hidden cursor-pointer items-center gap-2 text-secondary-shade transition hover:text-primary xl:inline-flex" aria-label="Se connecter">
                        <i class="fa-regular fa-user text-[17px]"></i>
                        <span class="text-[13px] font-medium">Se connecter</span>
                    </button>
                @endauth

                @unless(auth()->check() && auth()->user()->isStaffMember())
                <button type="button" @click="favoritesOpen = true" class="relative text-secondary-shade transition hover:text-primary" aria-label="Favoris">
                    <i class="fa-regular fa-heart text-[17px]"></i>
                    <template x-if="$store.favorites.items.length > 0">
                        <span class="absolute -right-2 -top-2 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-[9px] font-bold text-white" x-text="$store.favorites.items.length"></span>
                    </template>
                </button>
                @endunless

                @if(auth()->check() && auth()->user()->isStaffMember())
                    <a href="{{ route('admin.dashboard') }}" class="text-secondary-shade transition hover:text-primary" aria-label="Back-office">
                        <i class="fa-solid fa-gauge text-[17px]"></i>
                    </a>
                @else
                    <button type="button" @click="$store.cart.open = true" class="relative text-secondary-shade transition hover:text-primary" aria-label="Panier">
                        <i class="fa-solid fa-bag-shopping text-[17px]"></i>
                        <template x-if="$store.cart.count > 0">
                            <span class="absolute -right-2 -top-2 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-[9px] font-bold text-white" x-text="$store.cart.count"></span>
                        </template>
                    </button>
                @endif

            </div>

        </div>

        {{-- Recherche déroulante --}}
        <div
            x-show="searchOpen"
            x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak
            @click.outside="searchOpen = false"
            class="absolute inset-x-0 top-full z-40 border-t border-b border-secondary-shade/10 bg-white"
        >
            <div
                x-data="{
                    q: '{{ addslashes(request('q', '')) }}',
                    results: [],
                    loading: false,
                    timer: null,
                    search() {
                        clearTimeout(this.timer);
                        if (this.q.trim().length < 2) { this.results = []; this.loading = false; return; }
                        this.loading = true;
                        this.timer = setTimeout(() => {
                            fetch('{{ route('search.suggestions') }}?q=' + encodeURIComponent(this.q))
                                .then((r) => r.json())
                                .then((data) => { this.results = data.products; this.loading = false; })
                                .catch(() => { this.loading = false; });
                        }, 250);
                    },
                }"
                x-init="$watch('searchOpen', (open) => { if (!open) { q = ''; results = []; } })"
                class="mx-auto max-w-[1600px] px-6 py-6 sm:px-10"
            >
                <form action="{{ route('search') }}" method="GET">
                    <input
                        type="search"
                        name="q"
                        x-model="q"
                        @input="search()"
                        placeholder="Rechercher une robe, un sac, un vase…"
                        autofocus
                        autocomplete="off"
                        class="w-full border-b border-secondary-shade/20 bg-transparent pb-3 font-display text-2xl italic text-secondary-shade outline-none placeholder:text-secondary-shade/30 focus:border-primary"
                    >
                </form>

                {{-- Résultats en temps réel --}}
                <template x-if="q.trim().length >= 2">
                    <div class="mt-6">
                        <template x-if="results.length > 0">
                            <div class="grid grid-cols-3 gap-5 sm:grid-cols-4 md:grid-cols-6">
                                <template x-for="product in results" :key="product.id">
                                    <a :href="product.url" class="group block">
                                        <div class="aspect-square overflow-hidden bg-grey-tint">
                                            <img :src="product.image" :alt="product.name" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                        </div>
                                        <p class="mt-2 truncate text-xs text-secondary-shade group-hover:text-primary" x-text="product.name"></p>
                                        <p class="text-xs font-medium text-primary" x-text="new Intl.NumberFormat('fr-FR').format(product.price) + ' FCFA'"></p>
                                    </a>
                                </template>
                            </div>
                        </template>

                        <template x-if="!loading && results.length === 0">
                            <p class="text-sm text-grey">Aucun résultat pour « <span x-text="q"></span> ».</p>
                        </template>

                        <a :href="'{{ route('search') }}?q=' + encodeURIComponent(q)" class="group mt-6 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade transition hover:text-primary">
                            Voir tous les résultats
                            <i class="fa-solid fa-arrow-right text-[10px] transition group-hover:translate-x-1"></i>
                        </a>
                    </div>
                </template>
            </div>
        </div>

    </div>


    {{-- Menu mobile (drawer gauche) : catégories, promo et compte — équivalent mobile de la nav desktop --}}
    <div x-show="mobileMenuOpen" x-cloak class="fixed inset-0 z-[65] xl:hidden">
        <div
            x-show="mobileMenuOpen"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            @click="mobileMenuOpen = false"
            class="absolute inset-0 bg-secondary-shade/30"
        ></div>

        <div
            x-show="mobileMenuOpen"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-250" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
            x-data="{ openCat: null }"
            class="absolute left-0 top-0 flex h-full w-full max-w-xs flex-col overflow-y-auto bg-white"
        >
            <div class="flex items-center justify-between border-b border-secondary-shade/10 px-6 py-5">
                <img src="{{ asset('images/logo_khalil_deco_full.png') }}" alt="Khalil Déco" class="h-14 w-auto">
                <button type="button" @click="mobileMenuOpen = false" class="text-secondary-shade transition hover:text-primary" aria-label="Fermer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <nav class="flex-1 px-2 py-3">
                @foreach($navCategories ?? [] as $universe)
                    <div class="border-b border-secondary-shade/10">
                        <div class="flex items-center justify-between">
                            <a
                                href="{{ route('catalog.show', $universe) }}"
                                @click="mobileMenuOpen = false"
                                class="block flex-1 px-4 py-3.5 text-sm font-medium uppercase tracking-[0.1em] text-secondary-shade transition hover:text-primary"
                            >
                                {{ $universe->name }}
                            </a>
                            @if($universe->children->isNotEmpty())
                                <button
                                    type="button"
                                    @click="openCat = openCat === {{ $universe->id }} ? null : {{ $universe->id }}"
                                    class="px-4 py-3.5 text-secondary-shade"
                                    aria-label="Sous-catégories {{ $universe->name }}"
                                >
                                    <i class="fa-solid fa-chevron-down text-[11px] transition-transform duration-200" :class="openCat === {{ $universe->id }} ? 'rotate-180' : ''"></i>
                                </button>
                            @endif
                        </div>

                        @if($universe->children->isNotEmpty())
                            <div
                                x-show="openCat === {{ $universe->id }}"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-cloak
                                class="bg-grey-tint/50 pb-2"
                            >
                                @foreach($universe->children as $child)
                                    <a
                                        href="{{ route('catalog.show', $child) }}"
                                        @click="mobileMenuOpen = false"
                                        class="block px-8 py-2.5 text-sm text-grey transition hover:text-primary"
                                    >
                                        {{ $child->name }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach

                <a
                    href="{{ route('search', ['promotions' => 1]) }}"
                    @click="mobileMenuOpen = false"
                    class="block border-b border-secondary-shade/10 px-4 py-3.5 text-sm font-semibold uppercase tracking-[0.1em] text-primary transition hover:text-primary-shade"
                >
                    Promo
                </a>
            </nav>

            <div class="border-t border-secondary-shade/10 px-4 py-5">
                @auth
                    <p class="px-2 pb-3 text-xs text-grey">Connecté en tant que <span class="font-medium text-secondary-shade">{{ auth()->user()->name }}</span></p>

                    <a href="{{ route('account.index') }}" @click="mobileMenuOpen = false" class="block px-2 py-2.5 text-sm text-secondary-shade transition hover:text-primary">
                        <i class="fa-solid fa-user mr-2 w-4 text-center"></i>Mon profil
                    </a>

                    @unless(auth()->user()->isStaffMember())
                        <a href="{{ route('account.orders') }}" @click="mobileMenuOpen = false" class="block px-2 py-2.5 text-sm text-secondary-shade transition hover:text-primary">
                            <i class="fa-solid fa-box mr-2 w-4 text-center"></i>Mes commandes
                        </a>
                        <a href="{{ route('account.returns') }}" @click="mobileMenuOpen = false" class="block px-2 py-2.5 text-sm text-secondary-shade transition hover:text-primary">
                            <i class="fa-solid fa-rotate-left mr-2 w-4 text-center"></i>Mes retours
                        </a>
                    @else
                        <a href="{{ route('admin.dashboard') }}" @click="mobileMenuOpen = false" class="block px-2 py-2.5 text-sm text-secondary-shade transition hover:text-primary">
                            <i class="fa-solid fa-gauge mr-2 w-4 text-center"></i>Back-office
                        </a>
                    @endunless

                    <form action="{{ route('logout') }}" method="POST" class="mt-2 border-t border-secondary-shade/10 pt-2">
                        @csrf
                        <button type="submit" class="block w-full px-2 py-2.5 text-left text-sm text-secondary-shade transition hover:text-primary">
                            <i class="fa-solid fa-arrow-right-from-bracket mr-2 w-4 text-center"></i>Se déconnecter
                        </button>
                    </form>
                @else
                    <button type="button" @click="mobileMenuOpen = false; $store.ui.openLogin()" class="flex w-full cursor-pointer items-center justify-center gap-2 bg-secondary-shade px-6 py-3.5 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary">
                        <i class="fa-regular fa-user"></i>
                        Se connecter
                    </button>
                @endauth
            </div>
        </div>
    </div>


    {{-- Panier flottant (drawer, section 33 du cahier des charges) — état réactif, mis à jour sans rechargement --}}
    <div x-show="$store.cart.open" x-cloak class="fixed inset-0 z-[60]">
        <div
            x-show="$store.cart.open"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            @click="$store.cart.open = false"
            class="absolute inset-0 bg-secondary-shade/30"
        ></div>

        <div
            x-show="$store.cart.open"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-250" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="absolute inset-y-[5%] right-0 flex w-[90%] max-w-md flex-col overflow-hidden bg-white shadow-2xl lg:inset-y-0 lg:right-0 lg:w-full lg:shadow-none"
        >
            <div class="flex items-center justify-between border-b border-secondary-shade/10 px-5 py-5 sm:px-8 sm:py-6">
                <h2 class="font-display text-xl italic text-secondary-shade sm:text-2xl">Mon panier</h2>
                <button type="button" @click="$store.cart.open = false" class="text-secondary-shade transition hover:text-primary" aria-label="Fermer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-5 sm:px-8">
                <template x-if="$store.cart.items.length === 0">
                    <div class="flex h-full flex-col items-center justify-center py-24 text-center">
                        <i class="fa-solid fa-bag-shopping mb-4 text-2xl text-secondary-shade/20"></i>
                        <p class="text-sm text-grey">Votre panier est vide.</p>
                    </div>
                </template>

                <template x-for="item in $store.cart.items" :key="item.product_id + ':' + item.variant_id">
                    <div class="border-b border-secondary-shade/10 py-4 sm:py-5">
                        <div class="flex items-center gap-3 sm:gap-4">
                            <a :href="item.url" class="h-20 w-16 shrink-0 overflow-hidden bg-grey-tint sm:h-24 sm:w-20">
                                <img :src="item.image" :alt="item.name" class="h-full w-full object-cover">
                            </a>
                            <div class="min-w-0 flex-1">
                                <a :href="item.url" class="text-xs font-medium text-secondary-shade hover:text-primary sm:text-sm" x-text="item.name"></a>
                                <template x-if="item.variant_label">
                                    <p class="mt-1 text-[11px] text-grey sm:text-xs" x-text="item.variant_label"></p>
                                </template>
                                <p class="mt-1 text-[11px] text-grey sm:text-xs" x-text="'Qté : ' + item.quantity"></p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-2">
                                <button type="button" @click="$store.cart.remove(item.product_id, item.variant_id)" class="text-secondary-shade/40 transition hover:text-primary" aria-label="Retirer du panier">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                                <p class="whitespace-nowrap text-xs font-medium text-secondary-shade sm:text-sm" x-text="new Intl.NumberFormat('fr-FR').format(item.subtotal) + ' FCFA'"></p>
                            </div>
                        </div>

                        {{-- Attributs (couleur, dimension...) choisis ici, au moment de la commande, plutôt qu'à l'ajout au panier --}}
                        <template x-if="item.needs_variant">
                            <div
                                x-data="{
                                    selected: {},
                                    toggleValue(attributeId, valueId) {
                                        this.selected[attributeId] = this.selected[attributeId] === valueId ? null : valueId;
                                    },
                                    get selectedValueIds() {
                                        return Object.values(this.selected).filter(v => v !== null && v !== undefined);
                                    },
                                    get matchedVariant() {
                                        if (this.selectedValueIds.length !== item.variant_options.attributes.length) return null;
                                        return item.variant_options.variants.find(v => v.values.length === this.selectedValueIds.length
                                            && this.selectedValueIds.every(id => v.values.includes(id))) ?? null;
                                    },
                                }"
                                class="mt-4 bg-primary-tint/40 p-4"
                            >
                                <p class="mb-3 text-xs font-medium text-primary-shade">
                                    <i class="fa-solid fa-circle-info mr-1.5"></i>Choisissez les options de cet article
                                </p>

                                <template x-for="attribute in item.variant_options.attributes" :key="attribute.id">
                                    <div class="mb-3">
                                        <p class="mb-1.5 text-[11px] font-medium uppercase tracking-[0.08em] text-secondary-shade/70" x-text="attribute.name"></p>
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="value in attribute.values" :key="value.id">
                                                <button
                                                    type="button"
                                                    @click="toggleValue(attribute.id, value.id)"
                                                    :class="attribute.type === 'color'
                                                        ? ['h-7 w-7 rounded-full', selected[attribute.id] === value.id ? 'ring-2 ring-offset-1 ring-secondary-shade' : 'ring-1 ring-secondary-shade/20']
                                                        : ['border px-3 py-1.5 text-xs', selected[attribute.id] === value.id ? 'border-secondary-shade text-secondary-shade' : 'border-secondary-shade/20 text-secondary-shade']"
                                                    :style="attribute.type === 'color' ? ('background-color: ' + (value.colorCode || '#ccc')) : ''"
                                                    :title="value.value"
                                                    x-text="attribute.type === 'color' ? '' : value.value"
                                                ></button>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <button
                                    type="button"
                                    x-show="matchedVariant"
                                    @click="if (matchedVariant) $store.cart.chooseVariant(item.product_id, matchedVariant.id)"
                                    class="w-full bg-secondary-shade py-2.5 text-xs font-semibold uppercase tracking-[0.08em] text-white transition hover:bg-primary"
                                >
                                    Valider
                                </button>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <template x-if="$store.cart.items.length > 0">
                <div class="border-t border-secondary-shade/10 px-5 py-5 sm:px-8 sm:py-6">
                    <div class="mb-5 flex items-center justify-between gap-3">
                        <span class="text-xs font-medium uppercase tracking-[0.1em] text-secondary-shade sm:text-sm">Sous-total</span>
                        <span class="whitespace-nowrap font-display text-lg italic text-secondary-shade sm:text-xl" x-text="new Intl.NumberFormat('fr-FR').format($store.cart.subtotal) + ' FCFA'"></span>
                    </div>
                    <a
                        href="{{ route('checkout.index') }}"
                        :class="$store.cart.items.some(i => i.needs_variant) ? 'pointer-events-none cursor-not-allowed bg-grey-tint text-grey/60' : 'bg-primary text-white hover:bg-primary-shade'"
                        class="block px-6 py-4 text-center text-xs font-semibold uppercase tracking-[0.15em] transition"
                    >
                        Commander
                    </a>
                    <a href="{{ route('cart.index') }}" class="mt-3 block px-6 py-3 text-center text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade transition hover:text-primary">
                        Voir le panier
                    </a>
                </div>
            </template>
        </div>
    </div>


    {{-- Favoris flottants (drawer, section 32 du cahier des charges — stockage navigateur) --}}
    <div x-show="favoritesOpen" x-cloak class="fixed inset-0 z-[60]">
        <div
            x-show="favoritesOpen"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            @click="favoritesOpen = false"
            class="absolute inset-0 bg-secondary-shade/30"
        ></div>

        <div
            x-show="favoritesOpen"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-250" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="absolute inset-y-[5%] right-0 flex w-[90%] max-w-md flex-col overflow-hidden bg-white shadow-2xl lg:inset-y-0 lg:right-0 lg:w-full lg:shadow-none"
        >
            <div class="flex items-center justify-between border-b border-secondary-shade/10 px-5 py-5 sm:px-8 sm:py-6">
                <h2 class="font-display text-xl italic text-secondary-shade sm:text-2xl">Mes favoris</h2>
                <button type="button" @click="favoritesOpen = false" class="text-secondary-shade transition hover:text-primary" aria-label="Fermer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-5 sm:px-8">
                <template x-if="$store.favorites.items.length === 0">
                    <div class="flex h-full flex-col items-center justify-center py-24 text-center">
                        <i class="fa-regular fa-heart mb-4 text-2xl text-secondary-shade/20"></i>
                        <p class="text-sm text-grey">Aucun favori pour le moment.</p>
                    </div>
                </template>

                <template x-for="item in $store.favorites.items" :key="item.id">
                    <div class="flex items-center gap-3 border-b border-secondary-shade/10 py-4 sm:gap-4 sm:py-5">
                        <a :href="item.url" class="h-20 w-16 shrink-0 overflow-hidden bg-grey-tint sm:h-24 sm:w-20">
                            <img :src="item.image" :alt="item.name" class="h-full w-full object-cover">
                        </a>
                        <div class="min-w-0 flex-1">
                            <a :href="item.url" class="text-xs font-medium text-secondary-shade hover:text-primary sm:text-sm" x-text="item.name"></a>
                            <p class="mt-1 whitespace-nowrap text-[11px] text-grey sm:text-xs" x-text="new Intl.NumberFormat('fr-FR').format(item.price) + ' FCFA'"></p>
                        </div>
                        <button type="button" @click="$store.favorites.remove(item.id)" class="shrink-0 text-secondary-shade/40 transition hover:text-primary" aria-label="Retirer des favoris">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>


    {{-- Connexion / inscription flottantes (modale centrée, pas de page dédiée) --}}
    <div x-show="$store.ui.loginOpen" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-6">
        <div
            x-show="$store.ui.loginOpen"
            x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            @click="$store.ui.loginOpen = false"
            class="absolute inset-0 bg-secondary-shade/40"
        ></div>

        <div
            x-data="{
                loading: false,
                error: null,
                success: null,
                unverifiedEmail: null,
                resending: false,
                googleLoading: false,
                googlePopup: null,
                openGoogleAuth() {
                    this.error = null;
                    const url = '{{ route('auth.google') }}';
                    const w = 480, h = 640;
                    const left = window.screenX + (window.outerWidth - w) / 2;
                    const top = window.screenY + (window.outerHeight - h) / 2;
                    this.googlePopup = window.open(url, 'khalil-google-auth', `width=${w},height=${h},left=${left},top=${top}`);
                    if (! this.googlePopup) {
                        window.location.href = url;
                        return;
                    }
                    this.googleLoading = true;
                    const poll = setInterval(() => {
                        if (this.googlePopup && this.googlePopup.closed) {
                            clearInterval(poll);
                            this.googleLoading = false;
                        }
                    }, 400);
                },
                resendVerification() {
                    this.resending = true;
                    fetch('{{ route('verification.resend') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-XSRF-TOKEN': window.csrfToken(),
                        },
                        body: JSON.stringify({ email: this.unverifiedEmail }),
                    })
                        .then(async (response) => { const data = await response.json(); this.success = data.message; this.error = null; this.unverifiedEmail = null; })
                        .finally(() => { this.resending = false; });
                },
            }"
            x-init="
                window.addEventListener('message', (e) => {
                    if (e.origin !== window.location.origin || ! e.data || e.data.khalilGoogleAuth !== true) return;
                    googleLoading = false;
                    if (googlePopup) { googlePopup.close(); googlePopup = null; }
                    if (e.data.success) {
                        window.location.href = e.data.redirect;
                    } else {
                        error = e.data.message || 'La connexion avec Google a échoué. Réessayez.';
                    }
                });
            "
            x-show="$store.ui.loginOpen"
            x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            @click.outside="$store.ui.loginOpen = false"
            class="relative w-full max-w-md bg-white px-10 py-10"
        >
            <button type="button" @click="$store.ui.loginOpen = false" class="absolute right-6 top-6 text-secondary-shade transition hover:text-primary" aria-label="Fermer">
                <i class="fa-solid fa-xmark"></i>
            </button>

            {{-- Connexion --}}
            <template x-if="$store.ui.authMode === 'login'">
                <div>
                    <h2 class="font-display text-2xl italic text-secondary-shade sm:text-3xl">Connexion</h2>
                    <p class="mt-2 text-sm text-grey">Accédez à votre compte Khalil Déco.</p>

                    <button
                        type="button"
                        @click="openGoogleAuth()"
                        :disabled="googleLoading"
                        class="mt-8 flex w-full items-center justify-center gap-3 border border-secondary-shade/20 py-3.5 text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade transition hover:border-secondary-shade disabled:opacity-50"
                    >
                        <svg x-show="! googleLoading" class="h-4 w-4" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/>
                            <path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z"/>
                            <path fill="#FBBC05" d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332z"/>
                            <path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z"/>
                        </svg>
                        <i x-show="googleLoading" x-cloak class="fa-solid fa-circle-notch fa-spin text-sm"></i>
                        <span x-text="googleLoading ? 'En attente de Google…' : 'Continuer avec Google'"></span>
                    </button>

                    <div class="my-6 flex items-center gap-4">
                        <div class="h-px flex-1 bg-secondary-shade/10"></div>
                        <span class="text-[10px] uppercase tracking-[0.15em] text-grey">ou</span>
                        <div class="h-px flex-1 bg-secondary-shade/10"></div>
                    </div>

                    <form
                        action="{{ route('login') }}"
                        method="POST"
                        class="space-y-5"
                        @submit.prevent="
                            loading = true; error = null; success = null; unverifiedEmail = null;
                            const body = new FormData($el);
                            body.delete('_token');
                            fetch($el.action, { method: 'POST', headers: { Accept: 'application/json', 'X-XSRF-TOKEN': window.csrfToken() }, body })
                                .then(async (response) => {
                                    if (response.status === 419) {
                                        error = 'Votre session a expiré (page restée ouverte trop longtemps). Rechargement…';
                                        setTimeout(() => window.location.reload(), 1500);
                                        return;
                                    }
                                    const data = await response.json();
                                    if (!response.ok) {
                                        error = Object.values(data.errors ?? {})[0]?.[0] ?? data.message ?? 'Identifiants incorrects.';
                                        if (data.unverified_email) { unverifiedEmail = data.unverified_email; }
                                        loading = false;
                                        return;
                                    }
                                    const redirectUrl = new URL(data.redirect ?? window.location.href, window.location.origin);
                                    redirectUrl.searchParams.delete('login');
                                    window.location.href = redirectUrl.toString();
                                })
                                .catch(() => { error = 'Une erreur est survenue. Réessayez.'; loading = false; })
                        "
                    >
                        @csrf
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Email</label>
                            <input type="email" name="email" required class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm text-secondary-shade outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Mot de passe</label>
                            <input type="password" name="password" required class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm text-secondary-shade outline-none focus:border-primary">
                        </div>

                        <template x-if="success">
                            <p class="text-sm text-green-600" x-text="success"></p>
                        </template>

                        <template x-if="unverifiedEmail">
                            <button type="button" @click.prevent="resendVerification()" :disabled="resending" class="text-xs font-semibold text-primary hover:underline disabled:opacity-50">
                                <span x-show="!resending">Renvoyer l'email de vérification</span>
                                <span x-show="resending">Envoi…</span>
                            </button>
                        </template>

                        <template x-if="error">
                            <p class="text-sm text-red-600" x-text="error"></p>
                        </template>

                        <button type="submit" :disabled="loading" class="w-full bg-primary px-6 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary-shade disabled:opacity-50">
                            <span x-show="!loading">Se connecter</span>
                            <span x-show="loading">Connexion…</span>
                        </button>
                    </form>

                    <p class="mt-6 text-center text-xs text-grey">
                        Pas encore de compte ?
                        <button type="button" @click="$store.ui.authMode = 'register'; error = null" class="font-semibold text-secondary-shade hover:text-primary">Créer un compte</button>
                    </p>
                </div>
            </template>

            {{-- Inscription --}}
            <template x-if="$store.ui.authMode === 'register'">
                <div>
                    <h2 class="font-display text-2xl italic text-secondary-shade sm:text-3xl">Créer un compte</h2>
                    <p class="mt-2 text-sm text-grey">Rejoignez Khalil Déco.</p>

                    <template x-if="success">
                        <div class="mt-8 border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-700">
                            <i class="fa-solid fa-envelope-circle-check mr-2"></i><span x-text="success"></span>
                        </div>
                    </template>

                    <template x-if="!success">
                    <div>

                    <button
                        type="button"
                        @click="openGoogleAuth()"
                        :disabled="googleLoading"
                        class="mt-8 flex w-full items-center justify-center gap-3 border border-secondary-shade/20 py-3.5 text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade transition hover:border-secondary-shade disabled:opacity-50"
                    >
                        <svg x-show="! googleLoading" class="h-4 w-4" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/>
                            <path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z"/>
                            <path fill="#FBBC05" d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332z"/>
                            <path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z"/>
                        </svg>
                        <i x-show="googleLoading" x-cloak class="fa-solid fa-circle-notch fa-spin text-sm"></i>
                        <span x-text="googleLoading ? 'En attente de Google…' : 'Continuer avec Google'"></span>
                    </button>

                    <div class="my-6 flex items-center gap-4">
                        <div class="h-px flex-1 bg-secondary-shade/10"></div>
                        <span class="text-[10px] uppercase tracking-[0.15em] text-grey">ou</span>
                        <div class="h-px flex-1 bg-secondary-shade/10"></div>
                    </div>

                    <form
                        action="{{ route('register') }}"
                        method="POST"
                        class="space-y-5"
                        @submit.prevent="
                            loading = true; error = null;
                            const body = new FormData($el);
                            body.delete('_token');
                            fetch($el.action, { method: 'POST', headers: { Accept: 'application/json', 'X-XSRF-TOKEN': window.csrfToken() }, body })
                                .then(async (response) => {
                                    if (response.status === 419) {
                                        error = 'Votre session a expiré (page restée ouverte trop longtemps). Rechargement…';
                                        setTimeout(() => window.location.reload(), 1500);
                                        return;
                                    }
                                    const data = await response.json();
                                    if (!response.ok) {
                                        error = Object.values(data.errors ?? {})[0]?.[0] ?? data.message ?? 'Inscription impossible.';
                                        loading = false;
                                        return;
                                    }
                                    success = data.message;
                                    loading = false;
                                })
                                .catch(() => { error = 'Une erreur est survenue. Réessayez.'; loading = false; })
                        "
                    >
                        @csrf
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Nom complet</label>
                                <input type="text" name="name" required class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm text-secondary-shade outline-none focus:border-primary">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Email</label>
                                <input type="email" name="email" required class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm text-secondary-shade outline-none focus:border-primary">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Mot de passe</label>
                                <input type="password" name="password" required class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm text-secondary-shade outline-none focus:border-primary">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade">Confirmer</label>
                                <input type="password" name="password_confirmation" required class="w-full border-b border-secondary-shade/20 bg-transparent py-2 text-sm text-secondary-shade outline-none focus:border-primary">
                            </div>
                        </div>

                        <template x-if="error">
                            <p class="text-sm text-red-600" x-text="error"></p>
                        </template>

                        <button type="submit" :disabled="loading" class="w-full bg-primary px-6 py-4 text-xs font-semibold uppercase tracking-[0.15em] text-white transition hover:bg-primary-shade disabled:opacity-50">
                            <span x-show="!loading">Créer mon compte</span>
                            <span x-show="loading">Création…</span>
                        </button>
                    </form>

                    </div>
                    </template>

                    <p class="mt-6 text-center text-xs text-grey">
                        Déjà un compte ?
                        <button type="button" @click="$store.ui.authMode = 'login'; error = null; success = null" class="font-semibold text-secondary-shade hover:text-primary">Se connecter</button>
                    </p>
                </div>
            </template>
        </div>
    </div>

</header>
