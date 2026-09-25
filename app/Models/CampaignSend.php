<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignSend extends Model
{
    public const PENDING = 'pending';

    public const SENT = 'sent';

    public const FAILED = 'failed';

    protected $fillable = [
        'campaign_id',
        'user_id',
        'email',
        'status',
        'error_message',
        'sent_at',
        'opened_at',
        'clicked_at',
        'click_count',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markSent(): void
    {
        $this->update(['status' => self::SENT, 'sent_at' => now(), 'error_message' => null]);
    }

    public function markFailed(string $message): void
    {
        $this->update(['status' => self::FAILED, 'error_message' => $message]);
    }

    /**
     * Idempotent — un client qui recharge son email plusieurs fois (image en cache rechargée,
     * client mail qui pré-charge les images) ne doit compter que comme une seule ouverture.
     */
    public function markOpened(): void
    {
        if ($this->opened_at === null) {
            $this->update(['opened_at' => now()]);
        }
    }

    /**
     * Un clic implique une ouverture (certains clients mail bloquent le pixel mais chargent bien
     * le lien cliqué) — donc renseigne aussi opened_at si ce n'est pas déjà fait.
     */
    public function recordClick(): void
    {
        $this->update([
            'opened_at' => $this->opened_at ?? now(),
            'clicked_at' => now(),
            'click_count' => $this->click_count + 1,
        ]);
    }
}
