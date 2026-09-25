<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Session de caisse (section 8 du cahier des charges multi-rôles) : ouverture avec un fonds de
 * caisse déclaré, opérations pendant la session, clôture avec solde théorique (calculé à partir
 * des vrais encaissements « espèces » enregistrés) vs montant réellement compté.
 */
class CashSession extends Model
{
    /**
     * Seuls les encaissements en espèces transitent physiquement par la caisse — les paiements
     * Wave/Orange Money/carte/à la livraison ne font pas partie du solde théorique compté ici.
     */
    public const CASH_PAYMENT_METHOD = 'especes';

    protected $fillable = [
        'user_id', 'closed_by', 'opening_amount', 'opened_at',
        'closing_declared_amount', 'closing_expected_amount', 'discrepancy', 'comment', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }

    /**
     * Solde théorique à un instant donné : fonds d'ouverture + encaissements espèces réussis -
     * remboursements espèces, sur la fenêtre de la session. Jamais une estimation : uniquement
     * les paiements réellement enregistrés dans `payments`.
     */
    public function expectedAmount(?Carbon $until = null): int
    {
        $until ??= now();

        $collected = (int) Payment::where('payments.status', 'success')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('orders.payment_method', self::CASH_PAYMENT_METHOD)
            ->whereBetween('payments.paid_at', [$this->opened_at, $until])
            ->sum('payments.amount');

        $refunded = (int) Payment::where('payments.status', 'refunded')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('orders.payment_method', self::CASH_PAYMENT_METHOD)
            ->whereBetween('payments.updated_at', [$this->opened_at, $until])
            ->sum('payments.amount');

        return $this->opening_amount + $collected - $refunded;
    }
}
