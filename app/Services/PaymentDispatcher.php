<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentEvent;

/**
 * Point d'entrée unique pour démarrer une tentative de paiement en ligne, quel que soit le
 * prestataire — le checkout initial (CheckoutController::store) et la relance après échec/
 * annulation (PaymentController::retry) partagent cette même répartition, pour ne jamais la
 * dupliquer entre les deux points d'appel.
 */
class PaymentDispatcher
{
    public function __construct(
        private readonly PayTechService $payTech,
        private readonly PayDunyaService $payDunya,
    ) {
    }

    /**
     * Wave/Orange Money/Carte repassent par PayTech (système précédent, remis en place) — Djamo
     * et Free Money, non couverts par PayTech, restent sur PayDunya. Les intégrations directes
     * (WaveService/WaveController, OrangeMoneyService/OrangeMoneyController) restent intactes
     * dans le code, simplement inutilisées pour l'instant.
     *
     * @return array{success: bool, redirect_url: ?string, message: ?string, payment: ?\App\Models\Payment}
     */
    public function initiate(Order $order): array
    {
        $result = match ($order->payment_method) {
            'wave', 'orange_money', 'carte' => $this->payTech->createPayment($order),
            'djamo', 'free_money' => $this->payDunya->createPayment($order),
            default => ['success' => false, 'redirect_url' => null, 'message' => 'Moyen de paiement non pris en charge.', 'payment' => null],
        };

        if ($result['success']) {
            PaymentEvent::record($order, 'payment_initiated', [
                'payment_id' => $result['payment']?->id,
                'reference' => $result['payment']?->transaction_id,
            ]);
        }

        return $result;
    }
}
