<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const STATUS_LABELS = [
        'pending' => 'En attente',
        'processing' => 'En cours',
        'success' => 'Payé',
        'failed' => 'Échoué',
        'cancelled' => 'Annulé',
        'refunded' => 'Remboursé',
    ];

    public const STATUS_TONES = [
        'pending' => 'amber',
        'processing' => 'blue',
        'success' => 'green',
        'failed' => 'red',
        'cancelled' => 'neutral',
        'refunded' => 'neutral',
    ];

    protected $fillable = [
        'order_id',
        'provider',
        'gateway',
        'transaction_id',
        'amount',
        'status',
        'raw_response',
        'paid_at',
    ];

    /**
     * Jeton PayTech (distinct de `transaction_id`, qui porte notre propre référence
     * ref_command) — extrait de la notification IPN brute quand elle est disponible, plutôt
     * que d'ajouter une colonne dédiée pour une donnée seulement utile en support ponctuel.
     */
    public function paytechToken(): ?string
    {
        if (! $this->raw_response) {
            return null;
        }

        $data = json_decode($this->raw_response, true);

        return $data['token'] ?? null;
    }

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
