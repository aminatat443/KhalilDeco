<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'email',
        'user_id',
        'unsubscribe_token',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->unsubscribed_at === null;
    }

    /**
     * Inscription (ou ré-inscription) — idempotent : soumettre deux fois le même email ne crée
     * pas de doublon et réactive un désabonnement précédent au lieu d'échouer.
     */
    public static function subscribe(string $email, ?User $user = null): self
    {
        $subscriber = static::firstOrNew(['email' => $email]);

        if (! $subscriber->exists) {
            $subscriber->unsubscribe_token = Str::random(48);
            $subscriber->subscribed_at = now();
        }

        $subscriber->user_id = $user?->id ?? $subscriber->user_id;
        $subscriber->unsubscribed_at = null;
        $subscriber->save();

        return $subscriber;
    }
}
