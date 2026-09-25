<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    /**
     * Catégories de contenu de l'ancien constructeur de campagne "par produits" — conservées
     * pour ne pas casser l'historique déjà enregistré, plus utilisées par le nouveau système
     * (voir CAMPAIGN_TYPES pour les 3 types actuels).
     */
    public const TYPES = [
        'nouveautes' => 'Nouveautés',
        'promotion' => 'Promotion',
        'reassort' => 'Réassort de stock',
        'annonce' => 'Annonce générale',
    ];

    public const TYPE_ABANDONED_CART = 'abandoned_cart';

    public const TYPE_LOW_STOCK_FAVORITE = 'low_stock_favorite';

    public const TYPE_PROMOTION_FAVORITE = 'promotion_favorite';

    public const TYPE_PROMOTION = 'promotion';

    public const TYPE_ACTIVE_PROMOTIONS = 'active_promotions';

    public const TYPE_NEW_ARRIVALS = 'new_arrivals';

    public const TYPE_NEWSLETTER = 'newsletter';

    public const CAMPAIGN_TYPES = [
        self::TYPE_ABANDONED_CART => 'Panier abandonné',
        self::TYPE_LOW_STOCK_FAVORITE => 'Favoris / stock faible',
        self::TYPE_PROMOTION_FAVORITE => 'Promotion (produit favori)',
        self::TYPE_PROMOTION => 'Promotion (produit favori)',
        self::TYPE_ACTIVE_PROMOTIONS => 'Promotions (catalogue)',
        self::TYPE_NEW_ARRIVALS => 'Nouveautés',
        self::TYPE_NEWSLETTER => 'Newsletter',
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SENT = 'sent';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FAILED = 'failed';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Brouillon',
        self::STATUS_SCHEDULED => 'Programmée',
        self::STATUS_PENDING => 'En attente',
        self::STATUS_PROCESSING => 'En cours',
        self::STATUS_SENT => 'Envoyée',
        self::STATUS_PARTIAL => 'Partiellement envoyée',
        self::STATUS_FAILED => 'Échec',
    ];

    protected $fillable = [
        'type',
        'campaign_type',
        'status',
        'subject',
        'subject_template',
        'title',
        'message',
        'image_url',
        'button_text',
        'button_url',
        'product_ids',
        'recipients_count',
        'sent_count',
        'failed_count',
        'is_automatic',
        'sent_at',
        'scheduled_at',
        'sent_by',
    ];

    protected function casts(): array
    {
        return [
            'product_ids' => 'array',
            'is_automatic' => 'boolean',
            'sent_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function sends(): HasMany
    {
        return $this->hasMany(CampaignSend::class);
    }

    public function products()
    {
        return Product::whereIn('id', $this->product_ids ?? [])
            ->with(['images' => fn ($q) => $q->orderBy('sort_order')])
            ->get();
    }

    public function campaignTypeLabel(): string
    {
        return self::CAMPAIGN_TYPES[$this->campaign_type] ?? $this->campaign_type;
    }

    /**
     * Objet unique par campagne à partir d'un modèle fixe — évite que Gmail et consorts
     * regroupent plusieurs envois distincts dans une même conversation à cause d'un objet
     * identique. L'administrateur ne modifie jamais l'objet lui-même : ce suffixe est ajouté
     * automatiquement à l'instant réel de l'envoi (voir CampaignDispatcher::activate()).
     *
     * @return string  ex. "Découvrez nos nouveautés chez Khalil Déco · 20/09", avec l'heure en
     *                 plus si une autre campagne du même type a déjà été créée aujourd'hui.
     */
    public static function uniqueSubject(string $template, string $campaignType, ?int $campaignId = null): string
    {
        $now = now();
        $subject = $template.' · '.$now->format('d/m');

        $collision = static::where('campaign_type', $campaignType)
            ->whereDate('created_at', $now->toDateString())
            ->where('subject', $subject)
            ->when($campaignId, fn ($query) => $query->where('id', '!=', $campaignId))
            ->exists();

        if (! $collision) {
            return $subject;
        }

        // L'heure seule ne suffit pas si deux campagnes se créent dans la même minute (deux
        // commandes automatiques qui se chevauchent, par exemple) : dès que l'identifiant réel
        // de la campagne est connu (appelé après sa création), il garantit une unicité absolue.
        return $campaignId ? $subject.' · #'.$campaignId : $subject.' · '.$now->format('H\hi:s');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Recalcule le statut global à partir des envois individuels — appelé après chaque envoi
     * pour que la liste "Campagnes" reflète toujours l'état réel sans requête supplémentaire.
     */
    public function refreshStatus(): void
    {
        $total = $this->sends()->count();
        $sent = $this->sends()->where('status', CampaignSend::SENT)->count();
        $failed = $this->sends()->where('status', CampaignSend::FAILED)->count();
        $pending = $total - $sent - $failed;

        $status = match (true) {
            $pending > 0 && $sent === 0 && $failed === 0 => self::STATUS_PENDING,
            $pending > 0 => self::STATUS_PROCESSING,
            $failed > 0 && $sent > 0 => self::STATUS_PARTIAL,
            $failed > 0 && $sent === 0 => self::STATUS_FAILED,
            default => self::STATUS_SENT,
        };

        $this->update([
            'sent_count' => $sent,
            'failed_count' => $failed,
            'status' => $status,
            'sent_at' => $pending === 0 ? ($this->sent_at ?? now()) : null,
        ]);
    }
}
