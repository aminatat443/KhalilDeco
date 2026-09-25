<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Administration') — Khalil Déco</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Appliqué avant le rendu pour éviter un flash clair au chargement d'une page en mode sombre --}}
    <script>
        if (localStorage.getItem('khalilshop-admin-theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
        // La sidebar démarre repliée et se déploie au survol, sauf si elle a été épinglée
        // (bouton en haut de la sidebar) lors d'une session précédente — préférence persistée
        // en localStorage puisque chaque navigation recharge la page (pas de SPA). Appliqué avant
        // l'hydratation d'Alpine pour éviter un flash repliée-puis-déployée à chaque page.
        if (localStorage.getItem('khalilshop-admin-sidebar-pinned') !== '1') {
            document.documentElement.classList.add('admin-sidebar-collapsed');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="admin-shell bg-white text-secondary-shade antialiased dark:bg-[#0f1615] dark:text-[#e7ece9]"
    x-data="{
        sidebarOpen: false,
        sidebarPinned: localStorage.getItem('khalilshop-admin-sidebar-pinned') === '1',
        sidebarCollapsed: localStorage.getItem('khalilshop-admin-sidebar-pinned') !== '1',
        sidebarHoverTimer: null,
    }"
>

    <div class="flex min-h-screen">

        {{-- Sidebar — repliée par défaut, se déploie au survol (desktop uniquement ; le tiroir
             mobile reste toujours en pleine largeur avec les libellés, piloté par sidebarOpen).
             Le survol seul n'est jamais qu'un état temporaire : sidebarPinned (activé par un clic
             sur le bouton en haut de la sidebar) est la seule chose qui la garde ouverte une fois
             la souris partie — priorité : épinglé > survolé > réduit. Un court délai sur la
             fermeture (et non sur l'ouverture) évite qu'un simple passage rapide de la souris sur
             la bordure ne la fasse clignoter. --}}
        <aside
            :class="`${sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'} ${sidebarCollapsed ? 'lg:w-20' : 'lg:w-64'}`"
            class="admin-sidebar fixed inset-y-0 left-0 z-40 w-64 shrink-0 -translate-x-full border-r border-secondary-shade/10 bg-white text-secondary-shade transition-[transform,width] duration-200 ease-[cubic-bezier(0.4,0,0.2,1)] dark:border-white/10 dark:bg-[#16201f] dark:text-white lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
            @mouseenter="
                clearTimeout(sidebarHoverTimer);
                sidebarCollapsed = false;
                document.documentElement.classList.remove('admin-sidebar-collapsed');
            "
            @mouseleave="
                if (sidebarPinned) return;
                clearTimeout(sidebarHoverTimer);
                sidebarHoverTimer = setTimeout(() => {
                    sidebarCollapsed = true;
                    document.documentElement.classList.add('admin-sidebar-collapsed');
                }, 200);
            "
        >

            <div class="flex h-full min-h-0 flex-col">

            <div class="flex h-16 shrink-0 items-center overflow-hidden border-b border-secondary-shade/10 px-5 dark:border-white/10">
                <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-2.5 overflow-hidden">
                    <img src="{{ asset('images/logo_khalil_deco_full.png') }}" alt="Khalil Déco" data-sidebar-full-logo class="h-12 w-auto shrink-0 dark:hidden" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                    <img src="{{ asset('images/logo_khalil_deco_full_dark.png') }}" alt="Khalil Déco" data-sidebar-full-logo class="hidden h-12 w-auto shrink-0 dark:block" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                    <img src="{{ asset('images/logo_khalil_deco_icon.png') }}" alt="Khalil Déco" data-sidebar-icon-logo class="hidden h-8 w-auto shrink-0" :class="sidebarCollapsed ? 'lg:block' : ''">
                </a>
            </div>

            {{-- Épingler la barre latérale : le survol continue de la déployer temporairement,
                 mais un clic ici la garde ouverte même une fois la souris partie (et referme au
                 clic suivant). Sans effet sur le tiroir mobile, qui n'a pas de mode réduit. --}}
            <div class="hidden shrink-0 items-center border-b border-secondary-shade/10 px-3 py-2 dark:border-white/10 lg:flex" :class="sidebarCollapsed ? 'justify-center' : 'justify-end'">
                <button
                    type="button"
                    @click="
                        clearTimeout(sidebarHoverTimer);
                        sidebarPinned = ! sidebarPinned;
                        sidebarCollapsed = ! sidebarPinned;
                        document.documentElement.classList.toggle('admin-sidebar-collapsed', sidebarCollapsed);
                        localStorage.setItem('khalilshop-admin-sidebar-pinned', sidebarPinned ? '1' : '0');
                    "
                    :title="sidebarPinned ? 'Réduire la barre latérale' : 'Garder la barre latérale ouverte'"
                    :aria-pressed="sidebarPinned ? 'true' : 'false'"
                    aria-label="Ouvrir/fermer la barre latérale"
                    class="flex h-8 w-8 shrink-0 items-center justify-center text-grey transition hover:bg-grey-tint hover:text-secondary-shade dark:text-white/50 dark:hover:bg-white/10 dark:hover:text-white"
                    :class="sidebarPinned ? 'bg-grey-tint text-secondary-shade dark:bg-white/10 dark:text-white' : ''"
                >
                    <i class="fa-solid fa-table-columns text-sm"></i>
                </button>
            </div>

            <nav class="relative flex-1 space-y-6 overflow-y-auto px-4 py-4 text-sm [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @php
                    $groups = [
                        [
                            'label' => 'Principal',
                            'links' => [
                                ['route' => 'admin.dashboard', 'group' => 'admin.dashboard', 'icon' => 'fa-gauge', 'label' => 'Tableau de bord'],
                            ],
                        ],
                        [
                            'label' => 'Catalogue',
                            'links' => [
                                ['route' => 'admin.products.index', 'group' => 'admin.products.*', 'icon' => 'fa-boxes-stacked', 'label' => 'Produits'],
                                ['route' => 'admin.categories.index', 'group' => 'admin.categories.*', 'icon' => 'fa-sitemap', 'label' => 'Catégories'],
                                ['route' => 'admin.attributes.index', 'group' => 'admin.attributes.*', 'icon' => 'fa-sliders', 'label' => 'Attributs'],
                            ],
                        ],
                        [
                            'label' => 'Ventes',
                            'links' => [
                                ['route' => 'admin.orders.index', 'group' => 'admin.orders.*', 'icon' => 'fa-bag-shopping', 'label' => 'Commandes'],
                                ['route' => 'admin.cash.index', 'group' => 'admin.cash.*', 'icon' => 'fa-cash-register', 'label' => 'Caisse'],
                                ['route' => 'admin.payments.index', 'group' => 'admin.payments.*', 'icon' => 'fa-money-check-dollar', 'label' => 'Paiements'],
                                ['route' => 'admin.finances.reports', 'group' => 'admin.finances.reports', 'icon' => 'fa-file-lines', 'label' => 'Rapports'],
                                ['route' => 'admin.finances.index', 'group' => 'admin.finances.*', 'icon' => 'fa-sack-dollar', 'label' => 'Finances'],
                                ['route' => 'admin.expenses.index', 'group' => 'admin.expenses.*', 'icon' => 'fa-receipt', 'label' => 'Dépenses'],
                                ['route' => 'admin.invoices.index', 'group' => 'admin.invoices.*', 'icon' => 'fa-file-invoice', 'label' => 'Factures'],
                                ['route' => 'admin.coupons.index', 'group' => 'admin.coupons.*', 'icon' => 'fa-tag', 'label' => 'Codes promo'],
                                ['route' => 'admin.reviews.index', 'group' => 'admin.reviews.*', 'icon' => 'fa-star', 'label' => 'Avis clients'],
                                ['route' => 'admin.returns.index', 'group' => 'admin.returns.*', 'icon' => 'fa-rotate-left', 'label' => 'Retours'],
                            ],
                        ],
                    ];

                    if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('viewAny', App\Models\StockMovement::class)) {
                        $groups[1]['links'][] = ['route' => 'admin.stock.index', 'group' => 'admin.stock.*', 'icon' => 'fa-dolly', 'label' => 'Mouvements de stock'];
                    }

                    // Certains liens de ce premier niveau (jusque-là toujours affichés, quel que
                    // soit le rôle) mènent à des pages réservées — ex. un Comptable n'a pas accès
                    // aux Avis clients. On les filtre ici pour que la sidebar ne propose plus de
                    // lien vers une page dont l'accès sera de toute façon refusé côté serveur.
                    $sidebarUser = auth()->user();
                    $linkGates = [
                        'admin.cash.index' => fn () => $sidebarUser->can('viewAny', App\Models\CashSession::class),
                        'admin.payments.index' => fn () => $sidebarUser->can('viewAny', App\Models\Payment::class),
                        'admin.finances.reports' => fn () => $sidebarUser->role_id === null || $sidebarUser->hasPermission('reports.view'),
                        'admin.expenses.index' => fn () => $sidebarUser->can('viewAny', App\Models\Expense::class),
                        'admin.attributes.index' => fn () => $sidebarUser->can('viewAny', App\Models\Attribute::class),
                        'admin.finances.index' => fn () => $sidebarUser->role_id === null || $sidebarUser->hasPermission('finances.view'),
                        'admin.invoices.index' => fn () => $sidebarUser->role_id === null || $sidebarUser->hasPermission('invoices.view'),
                        'admin.coupons.index' => fn () => $sidebarUser->can('viewAny', App\Models\Coupon::class),
                        'admin.reviews.index' => fn () => $sidebarUser->can('viewAny', App\Models\Review::class),
                        'admin.returns.index' => fn () => $sidebarUser->can('viewAny', App\Models\ProductReturn::class),
                    ];

                    foreach ($groups as $groupIndex => $group) {
                        $groups[$groupIndex]['links'] = array_values(array_filter(
                            $group['links'],
                            fn ($link) => ! isset($linkGates[$link['route']]) || $linkGates[$link['route']]()
                        ));
                    }
                    $groups = array_values(array_filter($groups, fn ($group) => count($group['links']) > 0));
                @endphp

                @foreach($groups as $group)
                    <div>
                        <p class="sidebar-label whitespace-nowrap px-3 text-[10px] font-semibold uppercase tracking-[0.18em] text-secondary-shade/40 dark:text-white/35" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $group['label'] }}</p>
                        <div class="mt-2 space-y-1">
                            @foreach($group['links'] as $link)
                                @php $active = request()->routeIs($link['group']); @endphp
                                <a
                                    href="{{ route($link['route']) }}"
                                    title="{{ $link['label'] }}"
                                    class="group flex items-center gap-3 border-l-2 px-3 py-2.5 transition {{ $active ? 'border-primary bg-grey-tint text-secondary-shade dark:border-primary dark:bg-white/5 dark:text-white' : 'border-transparent text-grey hover:bg-grey-tint/60 hover:text-secondary-shade dark:text-white/55 dark:hover:bg-white/5 dark:hover:text-white' }}"
                                >
                                    <i class="fa-solid {{ $link['icon'] }} w-4 shrink-0 text-center text-xs transition {{ $active ? 'text-primary' : 'text-grey/60 group-hover:text-secondary-shade/70 dark:text-white/40 dark:group-hover:text-white/70' }}"></i>
                                    <span class="sidebar-label whitespace-nowrap" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $link['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @php
                    $gate = app(\Illuminate\Contracts\Auth\Access\Gate::class);

                    $adminLinks = [];
                    if ($gate->check('viewAny', App\Models\User::class)) {
                        $adminLinks[] = ['route' => 'admin.users.index', 'group' => 'admin.users.*', 'icon' => 'fa-user-group', 'label' => 'Utilisateurs'];
                    }
                    if ($gate->check('viewAny', App\Models\Role::class)) {
                        $adminLinks[] = ['route' => 'admin.roles.index', 'group' => 'admin.roles.*', 'icon' => 'fa-user-shield', 'label' => 'Rôles et permissions'];
                    }
                    if ($gate->check('viewAny', App\Models\ActivityLog::class)) {
                        $adminLinks[] = ['route' => 'admin.activity-logs.index', 'group' => 'admin.activity-logs.*', 'icon' => 'fa-clock-rotate-left', 'label' => "Journal d'activité"];
                    }

                    $secondaryLinks = [];
                    if ($gate->check('viewAny', App\Models\Campaign::class)) {
                        $secondaryLinks[] = ['route' => 'admin.campaigns.index', 'group' => 'admin.campaigns.*', 'icon' => 'fa-envelope-open-text', 'label' => 'Campagnes email'];
                        $secondaryLinks[] = ['route' => 'admin.segments.index', 'group' => 'admin.segments.*', 'icon' => 'fa-users-viewfinder', 'label' => 'Segments clients'];
                    }
                    if ($gate->check('viewAny', App\Models\SecurityEvent::class)) {
                        $secondaryLinks[] = ['route' => 'admin.security.index', 'group' => 'admin.security.*', 'icon' => 'fa-shield-halved', 'label' => 'Sécurité'];
                    }
                    if ($gate->check('viewAny', App\Models\Setting::class)) {
                        $secondaryLinks[] = ['route' => 'admin.settings.edit', 'group' => 'admin.settings.*', 'icon' => 'fa-gear', 'label' => 'Configuration'];
                    }
                @endphp

                @if(count($adminLinks))
                    <div>
                        <p class="sidebar-label whitespace-nowrap px-3 text-[10px] font-semibold uppercase tracking-[0.18em] text-secondary-shade/40 dark:text-white/35" :class="sidebarCollapsed ? 'lg:hidden' : ''">Administration</p>
                        <div class="mt-2 space-y-1">
                            @foreach($adminLinks as $link)
                                @php $active = request()->routeIs($link['group']); @endphp
                                <a
                                    href="{{ route($link['route']) }}"
                                    title="{{ $link['label'] }}"
                                    class="group flex items-center gap-3 border-l-2 px-3 py-2.5 transition {{ $active ? 'border-primary bg-grey-tint text-secondary-shade dark:border-primary dark:bg-white/5 dark:text-white' : 'border-transparent text-grey hover:bg-grey-tint/60 hover:text-secondary-shade dark:text-white/55 dark:hover:bg-white/5 dark:hover:text-white' }}"
                                >
                                    <i class="fa-solid {{ $link['icon'] }} w-4 shrink-0 text-center text-xs transition {{ $active ? 'text-primary' : 'text-grey/60 group-hover:text-secondary-shade/70 dark:text-white/40 dark:group-hover:text-white/70' }}"></i>
                                    <span class="sidebar-label whitespace-nowrap" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $link['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(count($secondaryLinks))
                    <div>
                        <p class="sidebar-label whitespace-nowrap px-3 text-[10px] font-semibold uppercase tracking-[0.18em] text-secondary-shade/40 dark:text-white/35" :class="sidebarCollapsed ? 'lg:hidden' : ''">Marketing &amp; système</p>
                        <div class="mt-2 space-y-1">
                            @foreach($secondaryLinks as $link)
                                @php $active = request()->routeIs($link['group']); @endphp
                                <a
                                    href="{{ route($link['route']) }}"
                                    title="{{ $link['label'] }}"
                                    class="group flex items-center gap-3 border-l-2 px-3 py-2.5 transition {{ $active ? 'border-primary bg-grey-tint text-secondary-shade dark:border-primary dark:bg-white/5 dark:text-white' : 'border-transparent text-grey hover:bg-grey-tint/60 hover:text-secondary-shade dark:text-white/55 dark:hover:bg-white/5 dark:hover:text-white' }}"
                                >
                                    <i class="fa-solid {{ $link['icon'] }} w-4 shrink-0 text-center text-xs transition {{ $active ? 'text-primary' : 'text-grey/60 group-hover:text-secondary-shade/70 dark:text-white/40 dark:group-hover:text-white/70' }}"></i>
                                    <span class="sidebar-label whitespace-nowrap" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $link['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </nav>

            <div class="relative border-t border-secondary-shade/10 p-4 dark:border-white/10" x-data="{ accountOpen: false }" @click.outside="accountOpen = false">
                @php
                    $roleLabels = [
                        'client' => 'Client',
                        'gestionnaire' => 'Gestionnaire',
                        'admin' => 'Administrateur',
                        'super_admin' => 'Super admin',
                    ];
                    $roleValue = auth()->user()->role?->value ?? auth()->user()->role;
                    $roleDisplayName = auth()->user()->roleModel?->name ?? ($roleLabels[$roleValue] ?? $roleValue);
                @endphp

                <div
                    x-show="accountOpen"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-cloak
                    class="absolute bottom-full left-4 z-10 mb-2 w-48 border border-secondary-shade/10 bg-white py-2 shadow-sm dark:border-white/10 dark:bg-[#1a2b2a]"
                >
                    <a href="{{ route('account.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-xs text-secondary-shade transition hover:bg-grey-tint dark:text-white/70 dark:hover:bg-white/10 dark:hover:text-white">
                        <i class="fa-solid fa-user w-4 text-center text-[10px]"></i>
                        Mon profil
                    </a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 px-3 py-2.5 text-left text-xs text-secondary-shade transition hover:bg-grey-tint dark:text-white/70 dark:hover:bg-white/10 dark:hover:text-white">
                            <i class="fa-solid fa-arrow-right-from-bracket w-4 text-center text-[10px]"></i>
                            Se déconnecter
                        </button>
                    </form>
                </div>

                <button
                    type="button"
                    @click="accountOpen = ! accountOpen"
                    title="{{ auth()->user()->name }}"
                    class="flex w-full items-center gap-3 overflow-hidden border border-secondary-shade/10 bg-grey-tint px-3 py-2.5 transition hover:border-primary/30 dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10"
                    :class="sidebarCollapsed ? 'lg:justify-center lg:gap-0 lg:border-transparent lg:bg-transparent lg:px-0 lg:py-2 lg:dark:bg-transparent lg:hover:border-transparent lg:hover:bg-grey-tint lg:dark:hover:bg-white/10' : ''"
                >
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center bg-secondary-shade text-xs font-bold uppercase text-white">
                        {{ Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->map(fn($p) => mb_substr($p, 0, 1))->take(2)->join('') }}
                    </span>
                    <span class="sidebar-label min-w-0 flex-1 whitespace-nowrap text-left" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                        <span class="block truncate text-sm font-medium text-secondary-shade dark:text-white">{{ auth()->user()->name }}</span>
                        <span class="block truncate text-xs text-grey dark:text-white/40">{{ $roleDisplayName }}</span>
                    </span>
                    <i class="sidebar-label fa-solid fa-chevron-up shrink-0 text-[9px] text-grey/60 transition-transform duration-200 dark:text-white/40" :class="[accountOpen ? '' : 'rotate-180', sidebarCollapsed ? 'lg:hidden' : '']"></i>
                </button>

                <a href="{{ route('home') }}" title="Retour à la boutique" class="mt-1 flex items-center gap-3 overflow-hidden px-3 py-2.5 text-xs text-grey transition hover:bg-grey-tint hover:text-secondary-shade dark:text-white/60 dark:hover:bg-white/10 dark:hover:text-white">
                    <i class="fa-solid fa-arrow-left w-4 shrink-0 text-center text-[10px]"></i>
                    <span class="sidebar-label whitespace-nowrap" :class="sidebarCollapsed ? 'lg:hidden' : ''">Retour à la boutique</span>
                </a>
            </div>
            </div>
        </aside>

        <div
            x-show="sidebarOpen"
            x-cloak
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-secondary-shade/40 lg:hidden"
        ></div>

        <div class="min-w-0 flex-1">

            {{-- Topbar mobile --}}
            <div class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-secondary-shade/10 bg-white px-6 dark:border-white/10 dark:bg-[#16201f] lg:hidden">
                <button type="button" @click="sidebarOpen = !sidebarOpen" class="text-secondary-shade dark:text-white/70">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <img src="{{ asset('images/logo_khalil_deco_full.png') }}" alt="Khalil Déco" class="h-12 w-auto dark:hidden">
                <img src="{{ asset('images/logo_khalil_deco_full_dark.png') }}" alt="Khalil Déco" class="hidden h-12 w-auto dark:block">
                <div class="flex items-center gap-2">
                    <button type="button" @click="window.dispatchEvent(new CustomEvent('open-command-palette'))" aria-label="Rechercher" class="flex h-8 w-8 items-center justify-center text-secondary-shade/70 transition hover:text-primary dark:text-white/60">
                        <i class="fa-solid fa-magnifying-glass text-sm"></i>
                    </button>
                    <span class="hidden shrink-0 whitespace-nowrap border border-secondary-shade/15 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-secondary-shade dark:border-white/15 dark:text-white/70 sm:inline-block">{{ $roleDisplayName }}</span>
                    <x-theme-toggle />
                    <x-notification-bell />
                </div>
            </div>

            {{-- Topbar desktop — grille à 3 colonnes (1fr auto 1fr) pour que la barre de
                 recherche reste centrée sur toute la largeur, quelle que soit la longueur du
                 titre à gauche ou du groupe d'icônes à droite. --}}
            <div class="sticky top-0 z-20 hidden h-16 grid-cols-[1fr_auto_1fr] items-center gap-2 border-b border-secondary-shade/10 bg-white px-8 dark:border-white/10 dark:bg-[#16201f] lg:grid">
                <p class="min-w-0 truncate text-sm font-medium text-secondary-shade dark:text-white">@yield('title', 'Administration')</p>

                <button
                    type="button"
                    @click="window.dispatchEvent(new CustomEvent('open-command-palette'))"
                    class="flex w-72 items-center gap-2.5 border border-secondary-shade/15 bg-grey-tint/40 px-3.5 py-2 text-left text-xs text-grey transition hover:border-secondary-shade/30 dark:border-white/10 dark:bg-white/5 dark:text-white/40 dark:hover:border-white/20 justify-self-center"
                >
                    <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
                    <span class="flex-1">Rechercher…</span>
                    <span class="flex items-center gap-0.5 border border-secondary-shade/15 px-1.5 py-0.5 text-[10px] font-semibold text-secondary-shade/60 dark:border-white/15 dark:text-white/40">Ctrl K</span>
                </button>

                <div class="flex items-center justify-end gap-3">
                    <span class="shrink-0 whitespace-nowrap border border-secondary-shade/15 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-secondary-shade dark:border-white/15 dark:text-white/70">{{ $roleDisplayName }}</span>
                    <x-theme-toggle />
                    <x-notification-bell />
                </div>
            </div>

            <main class="p-6 sm:p-10">
                <div class="mx-auto max-w-6xl">
                    @yield('content')
                </div>
            </main>
        </div>

    </div>

    {{-- Notifications flash (toasts) — auto-masquées après quelques secondes, fermables à tout moment --}}
    <div class="pointer-events-none fixed inset-x-4 top-4 z-[100] flex flex-col items-center gap-3 sm:inset-x-auto sm:right-4 sm:items-end" aria-live="polite">
        @if(session('status'))
            <div
                x-data="{ show: false }"
                x-init="setTimeout(() => show = true, 30); setTimeout(() => show = false, 5000)"
                x-show="show"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2 sm:translate-x-4 sm:translate-y-0" x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="pointer-events-auto flex w-full max-w-sm items-start gap-3 border border-secondary-shade/10 bg-white px-5 py-4 text-sm text-secondary-shade shadow-sm dark:border-white/10 dark:bg-[#16201f] dark:text-white"
            >
                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-400">
                    <i class="fa-solid fa-check text-[11px]"></i>
                </span>
                <span class="flex-1">{{ session('status') }}</span>
                <button type="button" @click="show = false" aria-label="Fermer" class="shrink-0 text-secondary-shade/40 transition hover:text-secondary-shade dark:text-white/40 dark:hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endif

        @if($errors->any())
            <div
                x-data="{ show: false }"
                x-init="setTimeout(() => show = true, 30)"
                x-show="show"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2 sm:translate-x-4 sm:translate-y-0" x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="pointer-events-auto flex w-full max-w-sm items-start gap-3 border border-red-200 bg-white px-5 py-4 text-sm text-secondary-shade shadow-sm dark:border-red-500/20 dark:bg-[#16201f] dark:text-white"
            >
                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400">
                    <i class="fa-solid fa-triangle-exclamation text-[11px]"></i>
                </span>
                <ul class="flex-1 list-disc space-y-1 pl-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" @click="show = false" aria-label="Fermer" class="shrink-0 text-secondary-shade/40 transition hover:text-secondary-shade dark:text-white/40 dark:hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endif
    </div>

    {{-- Aperçu de facture en modal — déclenché depuis n'importe quelle page admin via
         window.dispatchEvent(new CustomEvent('open-invoice-preview', { detail: { url, label } })) --}}
    <div
        x-data="{
            open: false,
            loading: false,
            url: null,
            label: null,
            phone: null,
            shareOpen: false,
            copied: false,
            waLink() {
                const digits = (this.phone || '').replace(/[^\d]/g, '');
                if (! digits) return null;
                const withCountry = digits.length === 9 ? '221' + digits : digits;
                const text = `Bonjour, voici votre facture ${this.label} — Khalil Déco : ${this.url}`;
                return `https://wa.me/${withCountry}?text=${encodeURIComponent(text)}`;
            },
            async copyLink() {
                try {
                    await navigator.clipboard.writeText(this.url);
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2000);
                } catch (e) {}
                this.shareOpen = false;
            },
        }"
        x-init="$watch('open', (value) => window.__invoicePreviewOpen = value)"
        x-on:open-invoice-preview.window="open = true; loading = true; shareOpen = false; label = $event.detail.label; url = $event.detail.url; phone = $event.detail.phone ?? null; setTimeout(() => loading = false, 1200)"
        x-on:keydown.escape.window="open = false"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[110] flex items-center justify-center p-4 sm:p-8"
    >
        <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-0 bg-secondary-shade/60" @click="open = false"></div>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="relative flex h-full w-full max-w-3xl flex-col bg-white shadow-xl dark:bg-[#16201f]"
        >
            <div class="flex shrink-0 items-center justify-between border-b border-secondary-shade/10 px-5 py-3.5 dark:border-white/10">
                <p class="flex items-center gap-2 text-sm font-semibold text-secondary-shade dark:text-white">
                    <i class="fa-solid fa-file-invoice text-primary"></i>
                    Facture <span x-text="label"></span>
                </p>
                <div class="flex items-center gap-2">
                    <div class="relative" @click.outside="shareOpen = false">
                        <button type="button" @click="shareOpen = ! shareOpen" class="flex h-8 w-8 items-center justify-center text-grey/60 transition hover:text-primary dark:text-white/50" title="Partager" aria-label="Partager">
                            <i class="fa-solid fa-share-nodes text-sm"></i>
                        </button>
                        <div
                            x-show="shareOpen"
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="absolute right-0 z-10 mt-2 w-52 border border-secondary-shade/10 bg-white py-2 shadow-lg dark:border-white/10 dark:bg-[#1a2b2a]"
                        >
                            <a
                                :href="waLink()"
                                target="_blank"
                                @click="shareOpen = false"
                                x-show="waLink()"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-shade transition hover:bg-grey-tint dark:text-white/70 dark:hover:bg-white/10"
                            >
                                <i class="fa-brands fa-whatsapp text-green-600"></i>Envoyer sur WhatsApp
                            </a>
                            <p x-show="! waLink()" class="px-4 py-2.5 text-xs text-grey dark:text-white/40">Aucun numéro de téléphone renseigné.</p>
                            <button type="button" @click="copyLink()" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-secondary-shade transition hover:bg-grey-tint dark:text-white/70 dark:hover:bg-white/10">
                                <i class="fa-solid fa-link text-grey/60 dark:text-white/40"></i>Copier le lien
                            </button>
                        </div>
                    </div>
                    <a :href="url" target="_blank" class="flex h-8 w-8 items-center justify-center text-grey/60 transition hover:text-primary dark:text-white/50" title="Ouvrir dans un nouvel onglet" aria-label="Ouvrir dans un nouvel onglet">
                        <i class="fa-solid fa-arrow-up-right-from-square text-sm"></i>
                    </a>
                    <a :href="url" download class="flex h-8 w-8 items-center justify-center text-grey/60 transition hover:text-primary dark:text-white/50" title="Télécharger" aria-label="Télécharger">
                        <i class="fa-solid fa-download text-sm"></i>
                    </a>
                    <button type="button" @click="open = false" class="flex h-8 w-8 items-center justify-center text-grey/60 transition hover:text-secondary-shade dark:text-white/50 dark:hover:text-white" aria-label="Fermer">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>
            </div>
            <p x-show="copied" x-cloak x-transition class="shrink-0 bg-primary-tint px-5 py-2 text-xs font-medium text-primary-shade dark:bg-primary/10 dark:text-primary">
                <i class="fa-solid fa-circle-check mr-1.5"></i>Lien copié dans le presse-papiers.
            </p>
            <div class="relative flex-1 bg-grey-tint dark:bg-white/5">
                <div x-show="loading" class="absolute inset-0 flex items-center justify-center">
                    <i class="fa-solid fa-circle-notch fa-spin text-2xl text-primary"></i>
                </div>
                <template x-if="open">
                    <iframe :src="url" @load="loading = false" class="h-full w-full border-0"></iframe>
                </template>
            </div>
        </div>
    </div>

    {{-- Palette de commande — recherche globale (Ctrl+K / Cmd+K), voir resources/views/components/command-palette.blade.php --}}
    <x-command-palette />

    @stack('scripts')

</body>
</html>
