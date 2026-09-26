<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentEvent extends Model
{
    public const EVENT_LABELS = [
        'order_created' => 'Commande créée',
        'payment_initiated' => 'Paiement initié',
        'payment_success' => 'Paiement réussi',
        'payment_failed' => 'Paiement échoué',
        'payment_cancelled' => 'Paiement annulé',
        'order_confirmed' => 'Commande confirmée',
        'order_expired' => 'Commande expirée',
    ];

    protected $fillable = [
        'order_id',
        'payment_id',
        'event',
        'old_status',
        'new_status',
        'reference',
        'actor_type',
        'actor_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function label(): string
    {
        return self::EVENT_LABELS[$this->event] ?? $this->event;
    }

    /**
     * Point d'entrée unique pour journaliser une transition — appelé à chaque étape du cycle
     * commande/paiement (création, tentative, IPN, expiration...) plutôt que de laisser chaque
     * appelant construire la ligne à la main, pour garder une forme cohérente dans tout le code.
     */
    public static function record(Order $order, string $event, array $data = []): self
    {
        return self::create(array_merge([
            'order_id' => $order->id,
            'event' => $event,
            'new_status' => $order->status,
            'actor_type' => 'system',
        ], $data));
    }
}
