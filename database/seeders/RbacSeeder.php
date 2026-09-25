<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catalogue des permissions et rôles du système RBAC (cahier des charges « Gestion des
 * utilisateurs, rôles et permissions »). Idempotent : peut être rejoué sans dupliquer ni écraser
 * les rôles personnalisés créés depuis l'interface d'administration.
 */
class RbacSeeder extends Seeder
{
    /**
     * @var array<string, array<string, string>> module => [slug => libellé]
     */
    private const CATALOG = [
        'Utilisateurs' => [
            'users.view' => 'Voir les utilisateurs',
            'users.create' => 'Créer un utilisateur',
            'users.update' => 'Modifier un utilisateur',
            'users.deactivate' => 'Désactiver un utilisateur',
            'users.delete' => 'Supprimer un utilisateur',
            'users.manage_roles' => 'Gérer les rôles',
            'users.manage_permissions' => 'Gérer les permissions',
        ],
        'Produits' => [
            'products.view' => 'Voir les produits',
            'products.create' => 'Créer un produit',
            'products.update' => 'Modifier un produit',
            'products.delete' => 'Supprimer un produit',
            'products.edit_price' => 'Modifier les prix',
            'products.edit_promotions' => 'Modifier les promotions',
            'products.manage_images' => 'Gérer les images',
            'products.manage_variants' => 'Gérer les variantes',
        ],
        'Catégories' => [
            'categories.view' => 'Voir les catégories',
            'categories.create' => 'Créer une catégorie',
            'categories.update' => 'Modifier une catégorie',
            'categories.delete' => 'Supprimer une catégorie',
        ],
        'Stock' => [
            'stock.view' => 'Voir le stock',
            'stock.update' => 'Modifier le stock',
            'stock.entry' => 'Enregistrer une entrée',
            'stock.exit' => 'Enregistrer une sortie',
            'stock.movements' => 'Voir les mouvements',
            'stock.thresholds' => 'Gérer les seuils de stock',
        ],
        'Commandes' => [
            'orders.view' => 'Voir les commandes',
            'orders.view_details' => 'Voir les détails',
            'orders.update' => 'Modifier une commande',
            'orders.update_status' => 'Modifier le statut',
            'orders.cancel' => 'Annuler une commande',
            'orders.view_paid' => 'Voir les commandes payées',
            'orders.view_unpaid' => 'Voir les commandes impayées',
        ],
        'Paiements' => [
            'payments.view' => 'Voir les paiements',
            'payments.record' => 'Enregistrer un paiement',
            'payments.verify' => 'Vérifier un paiement',
            'payments.refund' => 'Rembourser un paiement',
            'payments.view_transactions' => 'Voir les transactions',
        ],
        'Finances' => [
            'finances.view' => 'Voir les finances',
            'finances.view_revenue' => 'Voir les revenus',
            'finances.view_expenses' => 'Voir les dépenses',
            'finances.manage_expenses' => 'Gérer les dépenses',
            'finances.view_reports' => 'Voir les rapports financiers',
            'finances.export' => 'Exporter les données',
        ],
        'Clients' => [
            'clients.view' => 'Voir les clients',
            'clients.update' => 'Modifier les clients',
            'clients.view_order_history' => "Voir l'historique des commandes",
            'clients.manage' => 'Gérer les clients',
        ],
        'Marketing' => [
            'marketing.view_campaigns' => 'Voir les campagnes',
            'marketing.create_campaign' => 'Créer une campagne',
            'marketing.update_campaign' => 'Modifier une campagne',
            'marketing.send_campaign' => 'Envoyer une campagne',
            'marketing.manage_templates' => "Gérer les modèles d'emails",
            'marketing.manage_newsletter' => 'Gérer la newsletter',
            'marketing.view_stats' => 'Voir les statistiques',
        ],
        'Factures' => [
            'invoices.view' => 'Voir les factures',
            'invoices.create' => 'Créer une facture',
            'invoices.update' => 'Modifier une facture',
            'invoices.download' => 'Télécharger une facture',
            'invoices.cancel' => 'Annuler une facture',
        ],
        'Configuration' => [
            'config.view' => 'Voir la configuration',
            'config.edit_delivery_zones' => 'Modifier les zones de livraison',
            'config.edit_delivery_rates' => 'Modifier les tarifs de livraison',
            'config.manage_payment_methods' => 'Gérer les moyens de paiement',
            'config.edit_general_settings' => 'Modifier les paramètres généraux',
        ],
        'Rapports' => [
            'reports.view' => 'Voir les rapports',
            'reports.export' => 'Exporter les rapports',
        ],
        'Caisse' => [
            'cash.open' => 'Ouvrir la caisse',
            'cash.close' => 'Clôturer la caisse',
            'cash.view' => "Voir l'historique de caisse",
        ],
    ];

    /**
     * @var array<string, array<int, string>> slug de rôle => liste de slugs de permissions
     * (vide = déduit dynamiquement, cf. Super Admin/Administrateur ci-dessous)
     */
    private const ROLE_PERMISSIONS = [
        'caissier' => [
            'orders.view', 'orders.view_details', 'orders.view_paid', 'orders.view_unpaid',
            'payments.view', 'payments.record', 'payments.verify',
            'invoices.view', 'invoices.download',
            'cash.open', 'cash.close', 'cash.view',
        ],
        'comptable' => [
            'orders.view', 'orders.view_details', 'orders.view_paid', 'orders.view_unpaid',
            'payments.view', 'payments.view_transactions', 'payments.refund',
            'invoices.view', 'invoices.create', 'invoices.update', 'invoices.download', 'invoices.cancel',
            'finances.view', 'finances.view_revenue', 'finances.view_expenses', 'finances.manage_expenses', 'finances.view_reports', 'finances.export',
            'reports.view', 'reports.export',
            'cash.view',
        ],
        'gestionnaire-stock' => [
            'products.view', 'products.update', 'products.manage_images', 'products.manage_variants',
            'categories.view',
            'stock.view', 'stock.update', 'stock.entry', 'stock.exit', 'stock.movements', 'stock.thresholds',
        ],
        'responsable-marketing' => [
            'products.view', 'products.edit_promotions',
            'marketing.view_campaigns', 'marketing.create_campaign', 'marketing.update_campaign',
            'marketing.send_campaign', 'marketing.manage_templates', 'marketing.manage_newsletter', 'marketing.view_stats',
        ],
        'service-client' => [
            'clients.view', 'clients.update', 'clients.view_order_history', 'clients.manage',
            'orders.view', 'orders.view_details', 'orders.update_status',
        ],
        'gestionnaire-boutique' => [
            'products.view', 'products.create', 'products.update', 'products.edit_promotions',
            'products.manage_images', 'products.manage_variants',
            'categories.view', 'categories.create', 'categories.update',
            'stock.view', 'stock.update', 'stock.entry', 'stock.exit', 'stock.movements', 'stock.thresholds',
            'orders.view', 'orders.view_details', 'orders.update', 'orders.update_status', 'orders.view_paid', 'orders.view_unpaid',
            'clients.view', 'clients.view_order_history',
            'invoices.view', 'invoices.create', 'invoices.download',
            'marketing.view_campaigns', 'marketing.view_stats',
            'config.view',
            'reports.view',
            'cash.open', 'cash.close', 'cash.view',
        ],
    ];

    private const ROLE_LABELS = [
        'super-admin' => ['name' => 'Super Admin', 'description' => 'Accès total, non modifiable.'],
        'administrateur' => ['name' => 'Administrateur', 'description' => "Accès large à l'administration, ne peut pas modifier le Super Admin."],
        'gestionnaire-boutique' => ['name' => 'Gestionnaire de boutique', 'description' => 'Gestion courante du catalogue, du stock et des commandes.'],
        'caissier' => ['name' => 'Caissier', 'description' => 'Encaissement et suivi des commandes.'],
        'comptable' => ['name' => 'Comptable', 'description' => 'Suivi financier, paiements et factures.'],
        'gestionnaire-stock' => ['name' => 'Gestionnaire stock', 'description' => 'Gestion des niveaux de stock et des mouvements.'],
        'responsable-marketing' => ['name' => 'Responsable marketing', 'description' => 'Campagnes email, newsletter et promotions.'],
        'service-client' => ['name' => 'Service client', 'description' => 'Suivi des clients et de leurs commandes.'],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $allSlugs = [];

            foreach (self::CATALOG as $module => $permissions) {
                foreach ($permissions as $slug => $label) {
                    Permission::updateOrCreate(['slug' => $slug], ['module' => $module, 'label' => $label]);
                    $allSlugs[] = $slug;
                }
            }

            foreach (self::ROLE_LABELS as $slug => $meta) {
                $role = Role::updateOrCreate(
                    ['slug' => $slug],
                    ['name' => $meta['name'], 'description' => $meta['description'], 'is_system' => true],
                );

                $permissionSlugs = match ($slug) {
                    'super-admin', 'administrateur' => $allSlugs,
                    default => self::ROLE_PERMISSIONS[$slug] ?? [],
                };

                $ids = Permission::whereIn('slug', $permissionSlugs)->pluck('id');
                $role->permissions()->sync($ids);
            }

            // Rattache les comptes historiques déjà élevés (enum `role`) au rôle RBAC équivalent,
            // pour que la nouvelle interface « Rôles et permissions » les reflète correctement
            // sans changer leur comportement (l'ancien enum reste la source de vérité pour eux).
            $superAdminRole = Role::where('slug', 'super-admin')->first();
            $adminRole = Role::where('slug', 'administrateur')->first();
            $gestionnaireRole = Role::where('slug', 'gestionnaire-boutique')->first();

            User::whereNull('role_id')->where('role', 'super_admin')->update(['role_id' => $superAdminRole?->id]);
            User::whereNull('role_id')->where('role', 'admin')->update(['role_id' => $adminRole?->id]);
            User::whereNull('role_id')->where('role', 'gestionnaire')->update(['role_id' => $gestionnaireRole?->id]);
        });
    }
}
