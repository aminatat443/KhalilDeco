import './bootstrap';
import '@fortawesome/fontawesome-free/css/all.min.css';
import Alpine from 'alpinejs';

// Le jeton CSRF lu depuis le cookie XSRF-TOKEN (plutôt que la balise <meta>, figée au
// chargement de la page) est renouvelé par Laravel à chaque réponse — plus robuste pour
// les pages restées ouvertes longtemps, dont le jeton statique finit par expirer.
function csrfToken() {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}
window.csrfToken = csrfToken;

// Bascules "mise en avant" (slider hero / nouveauté) sur les listes produit admin — un simple
// clic sans rechargement de page, cohérent avec le reste du back-office. La classe active/inactive
// du bouton est mise à jour localement à partir de l'état renvoyé par le serveur.
const PRODUCT_FLAG_ACTIVE_CLASSES = ['bg-primary-tint', 'text-primary', 'dark:bg-primary/15'];
const PRODUCT_FLAG_INACTIVE_CLASSES = ['text-grey/40', 'hover:bg-grey-tint', 'hover:text-secondary-shade', 'dark:text-white/30', 'dark:hover:bg-white/10', 'dark:hover:text-white'];

// Confirmation native pour les formulaires marqués data-confirm — sauf ceux à l'intérieur d'un
// conteneur [data-ajax-status-form], qui gère lui-même sa propre confirmation avant de soumettre
// en AJAX (ex. la fiche commande) ; sans cette exclusion, la boîte de dialogue apparaîtrait deux
// fois sur ces pages-là.
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (! (form instanceof HTMLFormElement) || ! form.dataset.confirm) return;
    if (form.closest('[data-ajax-status-form]')) return;
    if (! confirm(form.dataset.confirm)) {
        e.preventDefault();
    }
});

window.toggleProductFlag = async function (event, url) {
    event.preventDefault();
    event.stopPropagation();
    const button = event.currentTarget;

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': csrfToken() },
        });
        if (! response.ok) return;

        const data = await response.json();
        const flag = Object.keys(data).find((key) => key !== 'status');
        const active = data[flag];

        button.classList.remove(...PRODUCT_FLAG_ACTIVE_CLASSES, ...PRODUCT_FLAG_INACTIVE_CLASSES);
        button.classList.add(...(active ? PRODUCT_FLAG_ACTIVE_CLASSES : PRODUCT_FLAG_INACTIVE_CLASSES));
    } catch (e) {}
};

document.addEventListener('alpine:init', () => {
    // Panier — état source de vérité côté serveur, hydraté au chargement puis mis à jour par
    // fetch (pas de rechargement de page à l'ajout, cohérent avec la section 33 du cahier des charges).
    // La taille/couleur peut être choisie plus tard, directement dans le panier, plutôt qu'à l'ajout.
    Alpine.store('cart', {
        items: [],
        count: 0,
        subtotal: 0,
        open: false,

        hydrate(data) {
            this.items = data.items ?? [];
            this.count = data.count ?? 0;
            this.subtotal = data.subtotal ?? 0;
        },

        async add(form) {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: new FormData(form),
            });

            if (!response.ok) {
                return false;
            }

            this.hydrate(await response.json());
            this.open = true;

            return true;
        },

        async chooseVariant(productId, variantId) {
            return this.postJson('/panier/variante', { product_id: productId, variant_id: variantId });
        },

        async remove(productId, variantId) {
            return this.postJson('/panier/retirer', { product_id: productId, variant_id: variantId });
        },

        async postJson(url, payload) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                return false;
            }

            this.hydrate(await response.json());

            return true;
        },
    });

    // État d'interface global — permet d'ouvrir la fenêtre de connexion depuis n'importe quelle
    // page (ex : le formulaire d'avis client), pas seulement depuis le header.
    Alpine.store('ui', {
        loginOpen: false,
        authMode: 'login',

        openLogin(mode = 'login') {
            this.authMode = mode;
            this.loginOpen = true;
        },
    });

    // Favoris — toujours utilisables sans compte (localStorage, section 32 du cahier des
    // charges) ; synchronisés côté serveur en plus dès qu'un client est connecté, seul cas où on
    // dispose d'un email à qui envoyer une alerte stock faible/promotion (voir hydrate() plus bas).
    Alpine.store('favorites', {
        items: JSON.parse(localStorage.getItem('khalilshop_favorites') || '[]'),
        syncEnabled: false,

        persist() {
            localStorage.setItem('khalilshop_favorites', JSON.stringify(this.items));
        },

        isFavorite(id) {
            return this.items.some((item) => item.id === id);
        },

        toggle(product) {
            const isFavorite = ! this.isFavorite(product.id);
            this.items = isFavorite ? [...this.items, product] : this.items.filter((item) => item.id !== product.id);
            this.persist();
            this.syncToServer(product.id, isFavorite);

            // Confirmation visuelle : le tiroir des favoris s'ouvre à l'ajout (pas au retrait) —
            // écouté par le header, qui détient l'état d'ouverture du tiroir (favoritesOpen).
            if (isFavorite) {
                window.dispatchEvent(new CustomEvent('favorite-added'));
            }
        },

        remove(id) {
            this.items = this.items.filter((item) => item.id !== id);
            this.persist();
            this.syncToServer(id, false);
        },

        syncToServer(productId, isFavorite) {
            if (! this.syncEnabled) return;

            fetch('/favoris', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': window.csrfToken() },
                body: JSON.stringify({ product_id: productId, is_favorite: isFavorite }),
            }).catch(() => {});
        },

        // Appelé une fois au chargement de la page. `serverItems` vaut null pour un invité
        // (localStorage reste la seule source) ; pour un client connecté, fusionne les favoris
        // ajoutés localement avant connexion avec ceux déjà en base, puis aligne les deux.
        async hydrate(serverItems) {
            if (! serverItems) return;

            this.syncEnabled = true;

            const localOnlyIds = this.items
                .map((item) => item.id)
                .filter((id) => ! serverItems.some((item) => item.id === id));

            if (localOnlyIds.length > 0) {
                try {
                    const response = await fetch('/favoris/synchroniser', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': window.csrfToken() },
                        body: JSON.stringify({ product_ids: localOnlyIds }),
                    });
                    const data = await response.json();
                    serverItems = data.items;
                } catch (e) {
                    return;
                }
            }

            this.items = serverItems;
            this.persist();
        },
    });

    // Filtrage/recherche en temps réel sans rechargement de page — utilisé par les listes du
    // back-office (produits, commandes, retours…) : le formulaire de recherche/filtre et la
    // pagination passent tous par fetch(), avec repli sur une navigation classique en cas d'échec.
    // `hasFilters` pilote l'affichage du lien "Réinitialiser" (<x-admin-filter-reset>) — recalculé
    // à chaque changement de filtre, pas seulement au chargement initial de la page, sinon le lien
    // resterait figé sur l'état d'origine après un filtrage en AJAX (sans rechargement).
    Alpine.data('ajaxFilter', (initialHasFilters = false) => ({
        loading: false,
        hasFilters: initialHasFilters,
        async apply(url) {
            this.loading = true;
            try {
                const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } });
                if (! response.ok) throw new Error('request failed');
                const data = await response.json();
                // Détruit les composants Alpine imbriqués existants (ex. les graphiques d'un
                // tableau de bord) avant de remplacer le HTML, puis réinitialise ceux du nouveau
                // contenu — sans ça, un x-data/x-init à l'intérieur du bloc remplacé (graphique,
                // mini-formulaire...) resterait inerte après un filtrage/changement de période en
                // AJAX, alors qu'il fonctionne au premier chargement de la page.
                window.Alpine.destroyTree(this.$refs.results);
                this.$refs.results.innerHTML = data.html;
                window.Alpine.initTree(this.$refs.results);
                window.history.pushState({}, '', url);
            } catch (e) {
                window.location = url;
            } finally {
                this.loading = false;
            }
        },
        submitForm(e) {
            const form = e.target;
            const params = new URLSearchParams(new FormData(form));
            this.hasFilters = [...params.entries()].some(([key, value]) => key !== 'page' && value !== '');
            this.apply(form.action.split('?')[0] + '?' + params.toString());
        },
        onResultsClick(e) {
            const link = e.target.closest('[data-pagination] a');
            if (! link) return;
            e.preventDefault();
            this.apply(link.href);
        },
    }));
});

window.Alpine = Alpine;

/**
 * Échelle dynamique pour les graphiques financiers/ventes de l'admin — jamais de maximum codé en
 * dur (500 000, 1 000 000...). Calcule un maximum "lisible" légèrement au-dessus de la vraie
 * valeur max des données, avec un pas rond (1/2/5/10 × une puissance de 10), en visant environ
 * 5 à 8 graduations quel que soit l'ordre de grandeur (18 000 comme 27 500 000).
 */
window.KhalilCharts = {
    /**
     * @param {number[]} values Toutes les valeurs affichées sur le graphique (un ou plusieurs jeux de données confondus)
     * @param {number} targetSteps Nombre d'intervalles visé (5 à 8 graduations en résultent)
     */
    niceScale(values, targetSteps = 7) {
        const finite = (values || []).filter((v) => typeof v === 'number' && isFinite(v));
        const rawMax = finite.length ? Math.max(0, ...finite) : 0;

        // Aucune valeur significative (jeu vide ou entièrement à 0) : une échelle minimale lisible
        // plutôt qu'un axe "0, 0, 0, 0" sans aucun repère.
        const effectiveMax = rawMax > 0 ? rawMax : 10000;

        const roughStep = effectiveMax / targetSteps;
        const magnitude = Math.pow(10, Math.floor(Math.log10(roughStep)));
        const residual = roughStep / magnitude;
        const niceResidual = residual <= 1 ? 1 : residual <= 2 ? 2 : residual <= 5 ? 5 : 10;
        const step = niceResidual * magnitude;

        let steps = Math.ceil(effectiveMax / step);
        // Une valeur de donnée qui toucherait exactement la dernière graduation manque de marge
        // visuelle — un palier de plus au-dessus.
        if (steps * step === effectiveMax) steps += 1;

        return { max: steps * step, stepSize: step, hasData: rawMax > 0 };
    },

    /**
     * Format compact pour les étiquettes de l'axe et les infobulles — jamais "500000" ni
     * "1000000.00" : "500 000" en dessous du million, "1 M" / "2,5 M" au-delà.
     */
    formatFCFA(value, withSuffix = true) {
        const suffix = withSuffix ? ' FCFA' : '';
        const abs = Math.abs(value);

        if (abs >= 1000000) {
            const millions = Math.round((value / 1000000) * 10) / 10;
            const label = Number.isInteger(millions) ? String(millions) : millions.toFixed(1).replace('.', ',');

            return label + ' M' + suffix;
        }

        return new Intl.NumberFormat('fr-FR').format(Math.round(value)) + suffix;
    },
};
Alpine.start();
