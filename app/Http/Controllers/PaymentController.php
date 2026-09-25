<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use App\Services\PaymentDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentDispatcher $payments,
        private readonly OrderService $orders,
    ) {
    }

    /**
     * Relance une nouvelle tentative de paiement pour une commande déjà créée — après une
     * annulation ou un échec, sans redemander les informations de livraison ni dépendre du
     * panier (déjà vidé au moment de la commande initiale).
     *
     * Le client peut aussi en profiter pour changer de moyen de paiement (ex. Wave a échoué,
     * il veut essayer Orange Money — ou finalement payer à la livraison) via le champ optionnel
     * `payment_method` du formulaire de la page de confirmation.
     */
    public function retry(Request $request, Order $order): RedirectResponse
    {
        abort_if($order->payment_method === 'cod', 404);
        abort_if($order->payment_status === 'paid', 404);
        abort_if($order->status === 'annulee', 404);

        $newMethod = $request->input('payment_method');

        if ($newMethod && in_array($newMethod, ['wave', 'orange_money', 'carte', 'djamo', 'free_money', 'cod'], true)) {
            if ($newMethod === 'cod') {
                return $this->switchToCashOnDelivery($order);
            }

            if ($newMethod !== $order->payment_method) {
                // Les tentatives précédentes (autre moyen) n'ont plus lieu d'être suivies.
                $order->payments()->whereIn('status', ['pending', 'processing'])->update(['status' => 'cancelled']);
                $order->update(['payment_method' => $newMethod]);
            }
        }

        $payment = $this->payments->initiate($order);

        if (! $payment['success']) {
            return redirect()->route('checkout.confirmation', $order)
                ->withErrors(['payment_method' => 'Le paiement en ligne est momentanément indisponible. '.($payment['message'] ?? '')]);
        }

        return redirect()->away($payment['redirect_url']);
    }

    /**
     * Le client renonce au paiement en ligne et choisit de payer à la livraison — aucune
     * passerelle à contacter, la commande devient immédiatement actionnable comme n'importe
     * quelle commande COD créée directement au checkout.
     */
    private function switchToCashOnDelivery(Order $order): RedirectResponse
    {
        $order->payments()->whereIn('status', ['pending', 'processing'])->update(['status' => 'cancelled']);

        $order->update([
            'payment_method' => 'cod',
            'status' => 'recue',
        ]);

        $this->orders->notifyPlaced($order);

        return redirect()->route('checkout.confirmation', $order);
    }
}
