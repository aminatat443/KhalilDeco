# SPEC.md — Spécification technique complétée KhalilShop

> Ce document complète le [CAHIER-DES-CHARGES.md](./CAHIER-DES-CHARGES.md) (conversion fidèle du PDF client) en tranchant les points que celui-ci laisse volontairement ou involontairement ouverts.
>
> **Statut : validé par le client (KhalilShop) le 2026-09-01.** Toutes les propositions marquées ⚠️ ci-dessous sont désormais actées (✅ DÉCIDÉ), à l'exception du budget/délai qui reste hors périmètre technique. Deux décisions supplémentaires ont été ajoutées suite à validation : le rôle **Super Administrateur** (§2.5) et la **palette de couleurs définitive** (§6, qui remplace la palette provisoire de la section 6 du cahier des charges).

---

## 1. Décisions techniques structurantes

### 1.1 Base de données — ✅ DÉCIDÉ (révisé le 2026-09-01)

Le cahier des charges (section 67) prescrit **PostgreSQL**. Le projet avait temporairement tourné sur MySQL le temps des premières migrations ; **le client a demandé un retour à PostgreSQL, conforme au document original, avant de poursuivre le développement.**

**Décision finale : PostgreSQL 14**, installé localement (`postgresql`, `postgresql-contrib`, extension PHP `php8.4-pgsql`). Base `khalilshop`, rôle applicatif dédié `khalil`.

**Migration effectuée :**
- Toutes les migrations existantes (27 tables) sont passées sur PostgreSQL **sans aucune modification de code** — Laravel abstrait les différences de syntaxe (types `enum`, `unsigned`, etc.) au niveau du grammar de schéma, indépendamment du driver.
- Au passage, un vrai bug latent a été découvert et corrigé : la migration `2026_08_28_122207_create_sessions_table.php` était un **doublon exact** de la création de `sessions` déjà faite dans `0001_01_01_000000_create_users_table.php`. Sur MySQL, cette redondance avait été contournée par des correctifs d'état ponctuels (tables orphelines supprimées à la main) sans jamais traiter la cause réelle. PostgreSQL, plus strict, l'a fait remonter immédiatement (`SQLSTATE[42P07]: Duplicate table`). **Le fichier redondant a été supprimé** — la bonne correction étant de retirer le doublon, pas de le contourner à nouveau.
- Compte Super Administrateur de Khalil re-seedé et vérifié sur la nouvelle base.

*Config `.env` : `DB_CONNECTION=pgsql`, `DB_PORT=5432`, `DB_USERNAME=khalil`.*

### 1.2 Moyens de paiement — ✅ DÉCIDÉ

Le cahier des charges (section 36) liste Wave, Orange Money, carte, paiement à la livraison comme *pistes*, sans trancher.

**Proposition pour la v1 :**

| Moyen | Intégration proposée | Phase |
|---|---|---|
| Wave | Wave Checkout (lien de paiement via leur API marchand) | v1 |
| Orange Money | API Orange Money Sénégal (ou agrégateur type PayTech/CinetPay qui couvre les deux) | v1 |
| Paiement à la livraison (COD) | Pas d'intégration technique — juste un statut de commande à valider manuellement en back-office | v1 |
| Carte bancaire | Via le même agrégateur (CinetPay/PayTech gèrent carte + Wave + OM en un seul contrat) | v1 ou v2 selon coût du contrat |

**Recommandation concrète : passer par un agrégateur unique (CinetPay ou PayTech)** plutôt que d'intégrer Wave et Orange Money séparément — un seul contrat, une seule intégration technique, un seul webhook de confirmation à gérer côté Laravel (`payments` table déjà prévue section 50).

*Décision requise du client : validation du choix d'agrégateur (dépend des frais de transaction négociés), et confirmation si le paiement à la livraison est activé dès le lancement ou seulement en v2.*

### 1.3 Langue et devise — ✅ DÉCIDÉ

Non mentionné dans le cahier des charges.

**Proposition : français uniquement, FCFA (XOF) uniquement en v1.** Pas de multi-devise ni multi-langue. Les textes seront tout de même externalisés dans les fichiers de langue Laravel (`lang/fr/*.php`) plutôt que codés en dur, pour ne pas fermer la porte à une v2 multilingue (anglais, pour le marché ouest-africain anglophone) sans réécriture.

---

## 2. Règles métier manquantes du cahier des charges

### 2.1 Retours et remboursements — ✅ DÉCIDÉ

Absent du cahier des charges alors que standard pour un site de mode (le client doit pouvoir vérifier une taille).

**Proposition :**
- Délai de retour : **7 jours** après réception (délai plus court qu'en Europe, cohérent avec une logistique locale au Sénégal).
- Conditions : article non porté, non lavé, étiquettes intactes.
- Motif obligatoire à la demande (taille, défaut, ne correspond pas à la description, autre).
- Statuts de retour : `Demandée → Acceptée/Refusée → Article reçu → Remboursée`.
- Remboursement : par défaut en **crédit boutique (wallet/avoir)**, remboursement sur le moyen de paiement d'origine en option si le prestataire de paiement le permet.
- Frais de retour : à la charge du client sauf si défaut/erreur KhalilShop.

*Impact base de données : ajouter une table `returns` (order_item_id, reason, status, refund_method, timestamps) — non présente dans le schéma section 50 du cahier des charges.*

### 2.2 TVA et facturation — ✅ DÉCIDÉ

Absent du cahier des charges.

**Proposition v1 :** prix affichés **TTC** (toutes taxes comprises), pas de ventilation TVA séparée à l'affichage — pratique courante du e-commerce B2C au Sénégal. Génération d'un **reçu de commande PDF simple** (numéro de commande, articles, total, adresse) envoyé par email, pas une facture fiscale formelle sauf demande explicite du client final. Si KhalilShop est assujetti à la TVA sénégalaise et a besoin de facturation formelle, ce point devra être validé avec son comptable — hors périmètre technique de ce document.

### 2.3 Gestion du stock — concurrence et annulation — ✅ DÉCIDÉ (règle d'implémentation)

Le cahier des charges (section 45) décrit l'affichage du stock mais pas les règles de concurrence.

**Règles retenues :**
- Le décrément de stock se fait **au moment de la confirmation de commande** (paiement validé, ou commande COD confirmée manuellement), pas au moment de l'ajout au panier — un article au panier n'est pas réservé.
- Décrément **atomique** via requête SQL (`decrement()` Eloquent avec contrainte `WHERE stock >= quantity`) pour éviter la survente en cas de commandes simultanées sur le dernier article.
- **Annulation de commande** (par le client avant expédition, ou par l'admin) → remise en stock automatique de la variante concernée.
- Rupture de stock détectée au moment du paiement (rare, cas de concurrence) → commande automatiquement marquée en erreur, remboursement déclenché, notification au client et à l'admin.

### 2.4 Validation de commande — ✅ DÉCIDÉ (règle d'implémentation)

- Paiement en ligne (Wave/OM/carte) réussi → commande passe automatiquement à `Confirmée`.
- Paiement à la livraison (COD) → commande reste en `Reçue` jusqu'à confirmation **manuelle** par un Gestionnaire/Administrateur en back-office (appel de vérification recommandé avant préparation, pratique courante pour limiter les faux numéros/canulars sur le COD).
- Pas de montant minimum de commande imposé, sauf sur les codes promo qui peuvent définir leur propre minimum (déjà prévu section 47 du cahier des charges).

### 2.5 Rôles et permissions détaillées — ✅ DÉCIDÉ

Le cahier des charges (section 59) prévoyait trois rôles (Client, Gestionnaire, Administrateur) et restait vague sur le périmètre du Gestionnaire. **Décision du client : ajout d'un quatrième rôle, Super Administrateur, occupé par Khalil, seul habilité à créer d'autres comptes Administrateur.**

**Hiérarchie des rôles :**

```
Super Administrateur (Khalil)
        │
        ▼  peut créer / révoquer
   Administrateur
        │
        ▼  peut créer / superviser
   Gestionnaire
        │
        ▼
     Client
```

**Matrice de permissions :**

| Ressource | Client | Gestionnaire | Administrateur | Super Administrateur |
|---|---|---|---|---|
| Produits (CRUD) | — | Créer / Modifier / Désactiver (pas de suppression définitive) | Accès complet, y compris suppression | Accès complet |
| Stock | — | Modifier | Accès complet | Accès complet |
| Commandes | Voir les siennes | Voir toutes, changer statut, confirmer, annuler | Accès complet | Accès complet |
| Promotions / codes promo | — | Voir seulement | Créer / modifier / supprimer | Créer / modifier / supprimer |
| Catégories | — | Voir seulement | Créer / modifier / supprimer | Créer / modifier / supprimer |
| Homepage (hero, bannières) | — | — | Accès complet | Accès complet |
| Comptes Gestionnaire | — | — | Créer / modifier / désactiver | Créer / modifier / désactiver |
| **Comptes Administrateur** | — | — | — | **Seul rôle habilité à créer / révoquer un Administrateur** |
| Rapports / dashboard financier | — | Voir chiffres opérationnels (commandes, stock) | Voir tout, y compris chiffre d'affaires | Voir tout |

**Règles d'implémentation :**
- Ajout d'une colonne `role` (enum : `client`, `gestionnaire`, `admin`, `super_admin`) sur la table `users`, avec Policies Laravel vérifiant `auth()->user()->role` pour chaque action ci-dessus.
- La création d'un compte Administrateur est une route protégée accessible uniquement au rôle `super_admin` (`Gate::allows('create-admin')`).
- Il ne peut exister qu'**un seul compte Super Administrateur** (celui de Khalil) — pas de mécanisme de création/transfert de ce rôle depuis l'interface ; un changement de Super Admin se ferait uniquement en base de données directement, en dernier recours.
- *Rationale sur le reste de la matrice :* un Gestionnaire gère l'opérationnel quotidien (stock, commandes, fiches produit) mais ne modifie pas la vitrine publique ni les comptes à privilèges — ce sont des actions réservées à l'Administrateur et au Super Administrateur.

**État actuel :** colonne `role` ajoutée à `users` (migration `2026_09_01_120000_add_role_to_users_table`), enum `App\Enums\Role` créé, méthodes `isSuperAdmin()` / `isAdmin()` / `isGestionnaire()` ajoutées au modèle `User`. Le compte Super Administrateur est seedé (`database/seeders/DatabaseSeeder.php`) avec un **email fictif temporaire `khalil@khalilshop.sn` et un mot de passe placeholder** — à remplacer par le véritable email de Khalil et un mot de passe définitif avant la mise en production (`php artisan tinker` ou un nouveau `db:seed` une fois l'info connue).

### 2.6 Volumétrie et dimensionnement — ✅ DÉCIDÉ (hypothèses de dimensionnement)

Non chiffré dans le cahier des charges. Hypothèses retenues pour dimensionner l'infra et les choix de cache/pagination (section 56) :

- Catalogue initial : **200 à 500 produits**, extensible à plusieurs milliers.
- Trafic cible v1 : quelques centaines de visiteurs/jour, pics lors des promotions.
- Ces hypothèses n'ont pas d'impact structurant sur l'architecture choisie (Laravel + MySQL + Cloudinary tient largement cette charge) — elles sont documentées pour mémoire, à ajuster si le client communique des chiffres réels.

---

## 3. Palette de couleurs définitive — ✅ DÉCIDÉ (corrigé le 2026-09-02)

Remplace la palette provisoire de la section 6 du cahier des charges.

> **Correctif :** une révision "v2" (doré comme primaire, espresso comme secondaire, terracotta comme tertiaire) avait été appliquée par erreur dans `resources/css/app.css` lors de la refonte visuelle. **Le client a confirmé vouloir la palette d'origine ci-dessous** — c'est la seule version valable, la "v2" est abandonnée et ne doit plus être référencée nulle part dans le code.

| Rôle | Base | Tint (clair) | Shade (foncé) |
|---|---|---|---|
| **Primary** (Corail) | `#d77a61` | `#FBF2EF` | `#c26e57` |
| **Secondary** (Dark Slate) | `#2F4F4F` | `#AFB7A9` | `#213737` |
| **Tertiary** (Sable/Doré) | `#D7B661` | `#E7D3A0` | `#C2A457` |
| **Grey** | `#333333` | `#f5f5f5` | `#212529` |
| **Neutre** | Blanc `#ffffff` | — | Noir `#000000` |

**Règle d'usage retenue (contrainte de contraste, revérifiée pour cette palette) :** le sable/doré tertiaire est une teinte claire — un texte de cette couleur sur fond blanc est peu lisible (ratio ~1,8:1, sous le seuil AA de 4,5:1). Règle appliquée dans tout le code :
- Accent de **texte** sur fond clair (blanc, tint) → **primary** (corail, contraste ~2,9:1, acceptable pour du texte large/gras ; utilisé pour les prix, libellés, liens de survol) — jamais tertiary en texte sur fond clair.
- Fond doré/sable (`bg-tertiary`) → réservé aux **fonds** (badges, dégradés, low-opacity décoratif), jamais en texte lisible ; s'il porte du texte, celui-ci doit être **secondary-shade** (foncé), jamais blanc (contraste blanc/doré ~1,7:1, illisible).
- Fond primary (corail) → texte **secondary-shade** (foncé), jamais blanc (contraste ~2,1:1, insuffisant).
- Fond secondary (slate foncé) → texte blanc (contraste ~8:1, excellent).
- Corps de texte / légendes → **grey** (`#333`), pas les gris par défaut de Tailwind, pour rester cohérent avec la charte.

**Typographie associée :**

| Propriété | Valeurs |
|---|---|
| Tailles | 12 / 20 / 24 / 32 / 40 / 48 / 62 px |
| Graisses | 400 (regular) / 700 (bold) |
| Line-height | 1.2 / 1.5 |
| Letter-spacing | 0 / -2px |
| Famille | Montserrat (texte courant) + **Fraunces** (titres, ajoutée pour une direction éditoriale plus premium que le style minimaliste initial — décision distincte de la palette, non remise en cause) |

**Variables CSS réelles** (`resources/css/app.css`, tokens Tailwind v4 `@theme` — pas de `tailwind.config.js` dans ce projet) :

```css
@theme {
    --font-sans: 'Montserrat', ui-sans-serif, system-ui, sans-serif;
    --font-display: 'Fraunces', ui-serif, Georgia, serif;

    --color-primary: #d77a61;
    --color-primary-tint: #FBF2EF;
    --color-primary-shade: #c26e57;
    --color-secondary: #2F4F4F;
    --color-secondary-tint: #AFB7A9;
    --color-secondary-shade: #213737;
    --color-tertiary: #D7B661;
    --color-tertiary-tint: #E7D3A0;
    --color-tertiary-shade: #C2A457;
    --color-grey: #333333;
    --color-grey-tint: #f5f5f5;
    --color-grey-shade: #212529;
}
```
\Pour Khalil Building, je te recommande une palette sobre, architecturale et premium, avec le blanc dominant.

🎨 Palette principale
Couleur	Code HEX	Utilisation
🤍 Blanc	#FFFFFF	Fond principal, espaces
🟢 Vert profond	#263F3A	Couleur principale, logo, navigation
⚫ Anthracite	#252525	Texte, titres, éléments forts
🟡 Sable	#C8A875	Petit accent, détails, CTA secondaires
◻️ Ivoire	#F5F3EE	Sections secondaires
◽ Gris clair	#E5E2DA	Bordures, séparateurs
🔘 Gris moyen	#6B6B67	Texte secondaire

🏗️ Répartition que je conseille

60 % — Blanc #FFFFFF
→ arrière-plans et espace visuel

20 % — Ivoire #F5F3EE
→ sections, fonds alternatifs

10 % — Vert profond #263F3A
→ identité, navigation, boutons principaux

7 % — Anthracite #252525
→ textes et titres

3 % — Sable #C8A875
→ touches premium

Pour le logo

Je ferais :

KHALIL → #252525
BUILDING → #263F3A
Symbole architectural → #263F3A avec une petite touche #C8A875

Le sable doit vraiment rester une petite touche, sinon le logo risque de devenir trop "luxe/décoration".

Pour le site

Fond : #FFFFFF
Sections : #F5F3EE
Titres : #252525
Texte : #6B6B67
Bouton principal : #263F3A
Hover : #1D302C
Accent : #C8A875
Bordures : #E5E2DA

*Note : palette confirmée par le client (corail/dark slate/sable), cohérente avec le positionnement "Mode & Lifestyle" de la section 66. En place dans `resources/css/app.css` et utilisée dans toutes les vues via les classes Tailwind générées automatiquement (`bg-primary`, `text-secondary-shade`, etc.) — aucun code hex brut dans les templates Blade. Les usages de texte accent sur fond clair ont été audités et basculés de `text-tertiary` vers `text-primary` (meilleur contraste, voir règle ci-dessus) ; les gris de contenu (légendes, paragraphes) utilisent `text-grey` plutôt que l'échelle grise par défaut de Tailwind — les bordures/fonds fonctionnels de formulaires (inputs, cases à cocher) restent en gris Tailwind neutre, un choix d'UI et non de marque.*

---

## 4. Modèle de données — état actuel du code vs cahier des charges

Le cahier des charges (section 50) liste les tables prévues. Voici l'état réel des migrations déjà écrites dans `database/migrations/`, avec les écarts :

| Table | Cahier des charges | Implémentation actuelle | Écart / note |
|---|---|---|---|
| `categories` | citée simplement | `id, parent_id (self FK), name, slug, description, image, is_active, sort_order` | Arborescence via `parent_id` (auto-relation) plutôt qu'une table `subcategories` séparée — plus flexible, permet N niveaux. **Remplace la table `subcategories` du cahier.** |
| `products` | citée simplement | `id, category_id, name, slug, description, model, price, old_price, material, stock, is_new, is_promo, is_featured, is_active` | `products.stock` sert aux produits **sans variante** (ex. décoration simple) ; pour un produit avec variantes, le stock réel est la somme des `product_variants.stock` — voir règle 3.1 ci-dessous. Pas de champ `sku` au niveau produit (le SKU est porté par la variante). |
| `product_variants` | citée simplement | `id, product_id, color_id (nullable), size_id (nullable), price (nullable), stock, sku (unique)` | `price` nullable = hérite du prix du produit parent si non renseigné. |
| `product_images` | citée simplement | `id, product_id, url, public_id, alt, sort_order` | Conforme à la section 49 (URL, public ID Cloudinary, ordre, alt text). |
| `colors` | citée simplement | `id, name, hex_code (nullable)` | Ajout de `hex_code` (non explicite dans le cahier) pour l'affichage des pastilles de couleur en UI — nécessaire pour la section 27. |
| `sizes` | citée simplement | `id, name, type (clothing/shoes)` | Le champ `type` permet de distinguer tailles vêtement (XS-XXL) et pointures (36-45) dans une seule table plutôt que deux. |
| `orders`, `order_items`, `addresses`, `favorites`, `reviews`, `coupons`, `coupon_usages`, `promotions`, `banners`, `payments`, `deliveries` | citées | **pas encore créées** | Prochaine étape de développement (voir §5). |
| `returns` | absente | **pas encore créée** | Ajout validé — voir §2.1 (retours non prévus dans le cahier des charges original). |
| `users.role` | absente | **pas encore créée** | Colonne enum à ajouter — voir §2.5 (Super Administrateur / Administrateur / Gestionnaire / Client). |

### 4.1 Règle de calcul du stock affiché

- **Produit avec variantes** (vêtements, chaussures) : le stock affiché en fiche produit est **par variante** (taille/couleur sélectionnée), jamais le champ `products.stock` qui est ignoré dans ce cas.
- **Produit sans variante** (ex. un vase unique, sans déclinaison) : le stock affiché est directement `products.stock`.
- Cette règle doit être appliquée de façon cohérente dans le `ProductService` (couche Services, section 52 du cahier) plutôt que dupliquée dans les contrôleurs/vues.

---

## 5. Prochaines étapes de développement (ordre recommandé)

1. ~~Colonne `role` sur `users` + compte Super Administrateur~~ — **fait** (§2.5, email fictif en attente du vrai).
2. ~~Migrations restantes~~ — **fait** : `addresses`, `coupons`, `orders`, `order_items`, `favorites`, `reviews`, `coupon_usages`, `promotions`, `banners`, `payments`, `deliveries`, `returns` (§2.1). 27 tables au total, toutes migrées avec succès.
3. ~~Modèles Eloquent + relations~~ — **fait** pour toutes les tables (section 51 du cahier), y compris la mise à jour des modèles `Category`/`Product`/`ProductVariant`/`ProductImage`/`Color`/`Size`/`User` qui étaient encore des coquilles vides. Relations testées de bout en bout (y compris les cascades de suppression).
4. ~~Policies pour la matrice de permissions~~ — **fait** : `ProductPolicy`, `CategoryPolicy`, `PromotionPolicy`, `CouponPolicy`, `BannerPolicy`, `OrderPolicy`, `UserPolicy` (gère aussi la règle "seul le Super Admin crée un Administrateur", §2.5). Auto-découvertes par Laravel (convention de nommage), pas d'enregistrement manuel nécessaire.
5. ~~Services~~ — **fait** : `ProductService` (règle de stock §4.1), `PromotionService` (prix effectif + promotions planifiées + validation des codes promo §47), `CartService` (panier en session, prix toujours recalculé serveur), `OrderService` (création de commande, confirmation avec décrément atomique du stock, annulation avec remise en stock — règles §2.3/§2.4). Testés de bout en bout via Tinker, y compris le cas de rupture de stock à la confirmation (la commande passe alors automatiquement en `annulee` sans toucher au stock).
6. Intégration paiement via l'agrégateur retenu (§1.2).
7. ~~Intégration de la palette de couleurs définitive~~ — **fait**, via les tokens `@theme` de Tailwind v4 dans `resources/css/app.css` (`primary`, `secondary`, `tertiary` + variantes tint/shade). Pas de `tailwind.config.js` dans ce projet (Tailwind v4 utilise une configuration CSS-first).
8. ~~Frontend boutique : header, homepage, catalogue~~ — **fait** :
   - `HomeController`, `CatalogController`, `ProductController`, `SearchController`, `CartController` créés, tous branchés sur les vrais Services/Modèles (aucune donnée statique restante).
   - Header : mega menu dynamique (Alpine.js) sur les 5 univers + sous-catégories réelles, recherche fonctionnelle, compteur panier réel — partagés via un `View::composer` (`AppServiceProvider`).
   - Homepage : sections catégories, nouveautés, éditoriales par univers, promotions — toutes alimentées par la base.
   - Catalogue (`products/index.blade.php`) : grille responsive 2/3/4 colonnes, filtres prix/nouveautés/promotions, tri, pagination (section 20-22 du cahier des charges).
   - Fiche produit : galerie, sélection couleur/taille avec désactivation intelligente des variantes en rupture (Alpine.js, section 28), prix effectif via `PromotionService`.
   - **Bug corrigé au passage** : `2026_08_28_122207_create_sessions_table.php` était un doublon exact d'une table déjà créée par `create_users_table` — supprimé (voir §1.1).
   - `CategorySeeder` (structure officielle section 2, 62 catégories/sous-catégories) et `DemoCatalogSeeder` (12 produits fictifs, uniquement pour vérifier le rendu en local — **à ne jamais lancer en production**, le vrai catalogue sera saisi via le back-office).
   - Vérifié de bout en bout : build Vite, toutes les pages en 200, prix affichés au format `15 000 FCFA` (espace, pas point), flux complet ajout au panier → page panier.
9. ~~Refonte visuelle~~ — **fait** : direction finale "minimalisme éditorial premium" (grands espaces blancs, typographie serif fine, une seule couleur d'accent à la fois), après plusieurs itérations (glassmorphism puis hyper-moderne abandonnés en cours de route à la demande du client). Mega menu plein largeur, panier et favoris en tiroirs flottants, connexion en fenêtre modale centrée.
10. ~~Panier réactif sans rechargement~~ — **fait** : store Alpine (`$store.cart`/`$store.favorites`), `CartService::summary()` en JSON, favoris persistés en `localStorage`.
    - **Bug corrigé au passage** : le composant `<x-product-card>` utilisait des directives Alpine (`@click`, `:class`, `@submit.prevent`) sans aucun `x-data` englobant dans les pages où il s'affiche (catalogue, accueil, recherche) — Alpine n'active ses directives qu'à l'intérieur d'un sous-arbre avec `x-data`. Corrigé par l'ajout d'un `x-data` vide sur le composant.
11. ~~Connexion réelle~~ — **fait** : `AuthController` (guard `web` standard de Laravel), fenêtre flottante branchée en AJAX avec erreurs inline, limitation à 5 tentatives/minute. Inscription **pas encore faite** (lien présent dans la modale mais inactif).
12. ~~Choix de la variante différé à la commande~~ — **fait** : ajout au panier sans sélection taille/couleur, sélecteur inline dans le panier (`CartService::assignVariant()` + `POST /panier/variante`), "Passer la commande" désactivé tant qu'une ligne attend sa sélection.
13. ~~Checkout 5 étapes~~ — **fait** (section 34) : `CheckoutController`, assistant en une page (Alpine, pas de rechargement entre étapes) — infos personnelles → adresse → livraison (zones `DeliverySeeder` : Dakar/Thiès/Autres régions) → paiement → récapitulatif. Seul le **paiement à la livraison** est fonctionnel (§1.2, agrégateur Wave/OM/carte pas encore choisi) ; les autres moyens sont visibles mais désactivés ("Bientôt disponible"). Page de confirmation avec numéro de commande, récapitulatif, statut. Testé de bout en bout par requêtes réelles : commande créée, panier vidé, statut `recue`/`pending` conforme à la règle §2.4 (confirmation manuelle requise pour le COD).
14. ~~Back-office admin~~ — **fait** (sections 40 à 49) :
    - Middleware `staff` (`App\Http\Middleware\EnsureUserIsStaff`) réservant `/admin` aux rôles Gestionnaire/Admin/Super Admin (docs/SPEC.md §2.5) ; layout dédié avec sidebar, cohérent visuellement avec la boutique.
    - **Tableau de bord** : chiffre d'affaires (commandes confirmées+), nombre de commandes/clients/produits, alertes de stock faible, produits populaires (ventes réelles via `orderItems`), ventes récentes.
    - **Produits** : CRUD complet + gestion des **images** (upload local sur le disque `public`, `Storage::url()` — Cloudinary pas encore configuré, voir note ci-dessous) + gestion des **variantes** (couleur/taille/stock/SKU) directement sur la page produit.
    - **Catégories** : CRUD avec arborescence univers/sous-catégorie, protection contre la suppression d'une catégorie contenant des produits ou sous-catégories.
    - **Commandes** : recherche/filtre par statut, détail complet, **confirmation manuelle qui décrémente réellement le stock** (`OrderService::confirm()`, règle §2.4), annulation (remise en stock), changement de statut logistique (préparation/expédition/livraison).
    - **Promotions** et **codes promo** : CRUD complets (sections 46-47).
    - **Bannières/homepage** : CRUD avec upload d'image (section 48).
    - **Équipe** : création de comptes Gestionnaire (par un Admin+) ou Administrateur (par le Super Admin uniquement), via les Policies déjà en place — aucune règle dupliquée.
    - **Bug corrigé au passage** : `$this->authorize()` provoquait une erreur fatale (`Call to undefined method`) dans tous les contrôleurs admin — le `Controller` de base du squelette Laravel 11/12 n'inclut plus le trait `AuthorizesRequests` par défaut. Ajouté sur la classe de base, un seul endroit à corriger pour tous les contrôleurs.
    - Testé de bout en bout par requêtes réelles avec le compte Super Admin de Khalil : création d'une catégorie → d'un produit → d'une variante → **confirmation d'une vraie commande passée côté boutique, avec vérification que le stock a été décrémenté exactement de la quantité commandée** (10 → 8 pour 2 unités).
15. ~~Inscription, espace client, avis clients~~ — **fait** (sections 37, 38, 39) :
    - **Inscription** : `AuthController::register()`, même fenêtre flottante que la connexion avec bascule entre les deux formulaires (`$store.ui.authMode`). Compte créé avec le rôle `client`, connexion automatique.
    - **Redirection invité** : plus de page de connexion dédiée existant, un invité qui accède à une route `auth` est renvoyé vers l'accueil avec `?login=1`, qui ouvre automatiquement la fenêtre de connexion (`redirectGuestsTo` dans `bootstrap/app.php`).
    - **État global `$store.ui`** : `loginOpen`/`authMode` sortis du `x-data` local du header vers un store Alpine, pour pouvoir ouvrir la connexion depuis n'importe quelle page (ex. le formulaire d'avis).
    - **Espace client** (`/mon-compte`) : profil, historique des commandes, détail avec **fil de suivi visuel** (Reçue → Confirmée → En préparation → Expédiée → En livraison → Livrée, ou bandeau distinct si annulée) — section 38 du cahier des charges.
    - **Avis clients** (section 39) : notation 1-5 + commentaire sur la fiche produit, un seul avis par client et par produit (`updateOrCreate`), soumis à modération (`is_approved = false` par défaut) ; moyenne et liste des avis publiés affichées publiquement. Modération admin dédiée (`Admin\ReviewController` + `ReviewPolicy`) : publier ou rejeter.
    - Testé de bout en bout par requêtes réelles : redirection invité → inscription → accès espace client → soumission d'avis (non publié) → publication par le Super Admin → affichage public confirmé.
    - Reste à faire côté HTTP : carnet d'adresses réutilisable au checkout, intégration paiement réelle, emails automatiques (section 61), intégration Cloudinary réelle (upload local en attendant).

16. ~~Connexion Google, retours, menu compte différencié~~ — **fait** :
    - **Connexion Google** (Socialite, déjà en dépendance) : boutons "Continuer avec Google" sur les deux formulaires, identification/création de compte par email (mot de passe aléatoire généré pour les nouveaux comptes, jamais utilisé). Nécessite `GOOGLE_CLIENT_ID`/`GOOGLE_CLIENT_SECRET` réels dans `.env` pour fonctionner en pratique — vérifié que le flux de redirection vers Google se déclenche correctement (302 vers `accounts.google.com`).
    - **Menu compte différencié par rôle** : le staff (Gestionnaire/Admin/Super Admin) n'achète pas sur la boutique — son menu déroulant n'a plus "Mes commandes"/"Mes retours", l'icône favoris disparaît entièrement du header, "Mon compte" devient "Mon profil" partout. Le menu client garde Mon profil/Mes commandes/Mes retours. Un bouton texte "Se connecter" (plus seulement une icône) apparaît pour les invités.
    - **Retours et remboursements** (section 39 bis / docs/SPEC.md §2.1) : jusqu'ici la table `returns` existait sans aucune UI. Ajout complet : demande de retour par le client (motif + description) depuis le détail de sa commande livrée, avec fenêtre de **7 jours** (approximée via `order->updated_at`, la date exacte de livraison n'étant pas tracée séparément — à améliorer si un `delivered_at` est ajouté un jour), page "Mes retours" listant le statut, traitement admin dédié (`Admin\ReturnController` + `ProductReturnPolicy`) avec les 5 statuts déjà définis en base (demandée/acceptée/refusée/article reçu/remboursée).
    - **Piège de nommage évité** : le modèle s'appelle `ProductReturn` (pas `Return`, mot réservé PHP) — la Policy doit donc s'appeler `ProductReturnPolicy` pour l'auto-découverte Laravel, pas `ReturnPolicy` comme on pourrait s'y attendre naïvement.
    - Testé de bout en bout par requêtes réelles : inscription client → commande → confirmation admin → passage en "livrée" → demande de retour → traitement admin → vérifié visuellement que les menus déroulants client et staff diffèrent bien comme attendu.

**Notes d'implémentation :**
- `ProductReturn` est le nom de classe du modèle pour la table `returns` (`Return` étant un mot réservé en PHP) — pointe explicitement vers `protected $table = 'returns'`.
- `promotions` (planification par catégorie/produit avec dates) est distinct des champs `products.old_price`/`is_promo` (affichage manuel ponctuel) — les deux mécanismes coexistent, voir le commentaire dans la migration `create_promotions_table` ; `PromotionService::effectivePrice()` donne la priorité à la promotion planifiée si elle est active.
- `orders`/`order_items` conservent un **snapshot** des informations client, adresse et produit au moment de l'achat (nom, prix, libellé variante) plutôt que de dépendre uniquement des FK — l'historique de commande reste donc intact même si un produit, une adresse ou un compte est modifié/supprimé plus tard.
- Le panier n'a pas de table dédiée : il vit en session (`CartService`), cohérent avec l'absence de table `carts` dans le cahier des charges et avec le panier "drawer" décrit section 33. Aucun article n'est réservé/décrémenté avant la confirmation de commande.

---

## 6. Points restant à faire trancher par le client KhalilShop

Tout est validé sauf :

- [ ] **Vrai email + mot de passe définitif du compte Khalil (Super Administrateur)** — un email fictif `khalil@khalilshop.sn` est utilisé en attendant (§2.5).
- [ ] Choix définitif de l'agrégateur de paiement entre CinetPay et PayTech (les deux couvrent Wave/OM/carte — le choix dépend des frais de transaction négociés) — §1.2
- [ ] Budget et délai — **absents du cahier des charges original**, à définir avec le client, aucune proposition n'est faite ici (hors périmètre technique)
