<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /**
     * "en_attente_paiement" est le statut initial d'une commande réglée en ligne (Wave/Orange
     * Money/Carte) tant que le paiement n'est pas confirmé par le serveur — jamais "recue", qui
     * resterait ambiguë (une commande "reçue" peut être traitée par l'équipe alors que
     * "en_attente_paiement" ne doit jamais l'être avant confirmation réelle du paiement). Le
     * paiement à la livraison reste sur "recue" dès la création, inchangé.
     */
    public const STATUS_LABELS = [
        'en_attente_paiement' => 'En attente de paiement',
        'recue' => 'Reçue', 'confirmee' => 'Confirmée', 'en_preparation' => 'En préparation',
        'expediee' => 'Expédiée', 'livree' => 'Livrée', 'annulee' => 'Annulée',
    ];

    public const STATUS_TONES = [
        'en_attente_paiement' => 'amber',
        'recue' => 'neutral', 'confirmee' => 'blue', 'en_preparation' => 'amber',
        'expediee' => 'primary', 'livree' => 'green', 'annulee' => 'red',
    ];

    public const PAYMENT_METHOD_LABELS = [
        'cod' => 'À la livraison',
        'wave' => 'Wave',
        'orange_money' => 'Orange Money',
        'carte' => 'Carte bancaire',
        'djamo' => 'Djamo',
        'free_money' => 'Free Money',
        'especes' => 'Espèces (en boutique)',
    ];

    /**
     * Statuts comptés comme "vente réelle" dans les tableaux de bord (chiffre d'affaires,
     * rapports financiers...) — reprend la liste jusque-là dupliquée dans DashboardController et
     * FinanceController, désormais centralisée ici pour les nouveaux services de tableau de bord
     * par rôle.
     */
    public const CONFIRMED_STATUSES = ['confirmee', 'en_preparation', 'expediee', 'livree'];

    protected $fillable = [
        'order_number',
        'user_id',
        'address_id',
        'coupon_id',
        'delivery_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'delivery_region',
        'delivery_zone',
        'delivery_city',
        'delivery_quartier',
        'delivery_address',
        'delivery_instructions',
        'subtotal',
        'delivery_fee',
        'discount',
        'total',
        'payment_method',
        'payment_status',
        'is_in_store',
        'status',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'is_in_store' => 'boolean',
        ];
    }

    public function paymentMethodLabel(): string
    {
        return self::PAYMENT_METHOD_LABELS[$this->payment_method] ?? $this->payment_method;
    }

    /**
     * Lien signé vers la facture PDF — valable sans connexion (partage WhatsApp/email au
     * client), en plus de l'accès normal (équipe connectée, ou client propriétaire).
     */
    public function invoiceUrl(): string
    {
        return \Illuminate\Support\Facades\URL::signedRoute('orders.invoice', ['order' => $this]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Référence pratique vers la zone actuelle — jamais la source du tarif/nom affichés sur une
     * commande déjà passée (voir delivery_zone/delivery_fee, figés au moment de la commande).
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function couponUsages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Commandes "en retard d'expédition" — engagées dans le pipeline logistique (confirmée,
     * préparation ou expédiée) mais vieilles de plus de 5 jours. Source unique de cette
     * définition, réutilisée à la fois par le tableau de bord Service client (le compteur de la
     * carte) et par le filtre de la page Commandes (le clic sur cette carte) — jamais deux
     * calculs différents pour la même donnée (section 14 du cahier des charges d'harmonisation).
     */
    public function scopeOverdueShipping($query)
    {
        return $query->whereIn('status', ['confirmee', 'en_preparation', 'expediee'])
            ->where('created_at', '<=', now()->subDays(5));
    }

    /**
     * Statut à afficher au client quand un retour a été demandé sur au moins un article de la
     * commande : prime sur le statut logistique normal (ex. "Expédiée") pour éviter de montrer
     * un statut obsolète pendant qu'un retour est en cours. Nécessite `items.returns` chargé.
     */
    public function returnStatusInfo(): ?array
    {
        $statuses = $this->items->flatMap(fn (OrderItem $item) => $item->returns->pluck('status'));

        if ($statuses->isEmpty()) {
            return null;
        }

        $labels = [
            'demandee' => 'Demande de retour en cours',
            'acceptee' => 'Retour accepté',
            'article_recu' => 'Article reçu (retour)',
            'refusee' => 'Retour refusé',
            'remboursee' => 'Retournée',
        ];

        // Priorité à l'état le plus "actif" quand les articles d'une même commande ont des
        // statuts de retour différents.
        foreach (['demandee', 'acceptee', 'article_recu', 'refusee', 'remboursee'] as $status) {
            if ($statuses->contains($status)) {
                return ['label' => $labels[$status], 'tone' => ProductReturn::STATUS_TONES[$status]];
            }
        }

        return null;
    }

    public function hasAnyReturn(): bool
    {
        return $this->items->contains(fn (OrderItem $item) => $item->returns->isNotEmpty());
    }
}
