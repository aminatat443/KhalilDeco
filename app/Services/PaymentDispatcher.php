<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentEvent;

/**
 * Point d'entrée pour démarrer une tentative de paiement en ligne pour une commande déjà créée —
 * n'est plus utilisé que par Djamo/Free Money (PayDunya). Wave/Orange Money/Carte (PayTech) sont
 * désormais gérés directement par CheckoutController via PayTechService::createPayment(), qui
 * opère sur une PaymentAttempt et non plus une Order : la commande n'est créée qu'après un
 * paiement réellement réussi (voir OrderService::createFromAttempt), donc plus tôt dans le
 * parcours qu'ici. PayDunyaService reste inchangé, cette classe continue de le router tel quel.
 */
class PaymentDispatcher
{
    public function __construct(
        private readonly PayDunyaService $payDunya,
    ) {
    }

    /**
     * @return array{success: bool, redirect_url: ?string, message: ?string, payment: ?\App\Models\Payment}
     */
    public function initiate(Order $order): array
    {
        $result = match ($order->payment_method) {
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
