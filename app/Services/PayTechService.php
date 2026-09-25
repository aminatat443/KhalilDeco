<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

/**
 * Paiement en ligne Wave/Orange Money/Carte bancaire via l'agrégateur PayTech (paytech.sn),
 * seule intégration de paiement en ligne de l'application — voir doc.paytech.sn pour la
 * référence de chaque paramètre/endpoint utilisé ici.
 *
 * Chaque tentative de paiement (une commande peut en avoir plusieurs après une annulation ou
 * un échec) a sa propre ligne `payments` et sa propre référence PayTech (ref_command) — jamais
 * le numéro de commande seul, qui ne serait pas unique à travers plusieurs tentatives.
 */
class PayTechService
{
    private const REQUEST_PAYMENT_URL = 'https://paytech.sn/api/payment/request-payment';

    private const TARGET_PAYMENT_MAP = [
        'wave' => 'Wave',
        'orange_money' => 'Orange Money',
        'carte' => 'Carte Bancaire',
    ];

    /**
     * Démarre une tentative de paiement : crée la ligne `payments` (statut "pending" puis
     * "processing"/"failed" selon la réponse), appelle PayTech, et renvoie l'URL de
     * redirection vers sa page de paiement hébergée.
     *
     * N'échoue jamais par exception (erreur réseau/API PayTech) — renvoie un tableau
     * ['success' => false, 'message' => ...] pour laisser l'appelant proposer une alternative
     * (ex. le paiement à la livraison).
     */
    public function createPayment(Order $order): array
    {
        $key = config('services.paytech.key');
        $secret = config('services.paytech.secret');

        if (! $key || ! $secret) {
            return ['success' => false, 'redirect_url' => null, 'message' => 'Paiement en ligne non configuré.', 'payment' => null];
        }

        $refCommand = $this->generateRefCommand($order);

        // payment.amount garde le vrai total de la commande (comptabilité/rapports) — seul le
        // montant envoyé à PayTech (item_price) est réduit, pour que les frais qu'ils ajoutent
        // côté client sur leur page de paiement retombent sur ce vrai total. Voir le commentaire
        // sur services.paytech.fee_rate.
        $sentAmount = $this->applyFeeCompensation((int) $order->total);

        $payment = $order->payments()->create([
            'provider' => $order->payment_method,
            'gateway' => 'paytech',
            'transaction_id' => $refCommand,
            'amount' => (int) $order->total,
            'status' => 'pending',
        ]);

        try {
            $response = Http::withHeaders([
                'API_KEY' => $key,
                'API_SECRET' => $secret,
            ])->asJson()->post(self::REQUEST_PAYMENT_URL, [
                'item_name' => 'Commande '.$order->order_number,
                'item_price' => $sentAmount,
                'ref_command' => $refCommand,
                'command_name' => 'Commande '.$order->order_number.' — Khalil Déco',
                'currency' => 'XOF',
                'env' => config('services.paytech.env', 'test'),
                'ipn_url' => config('services.paytech.ipn_url') ?: route('paytech.ipn'),
                'success_url' => $this->returnUrl('success_url', 'paytech.success', $order),
                'cancel_url' => $this->returnUrl('cancel_url', 'paytech.cancel', $order),
                'target_payment' => self::TARGET_PAYMENT_MAP[$order->payment_method] ?? null,
                'custom_field' => json_encode([
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'user_id' => $order->user_id,
                ]),
            ]);
        } catch (\Throwable $e) {
            report($e);
            $payment->update(['status' => 'failed', 'raw_response' => $e->getMessage()]);

            return ['success' => false, 'redirect_url' => null, 'message' => 'Service de paiement injoignable.', 'payment' => $payment];
        }

        $data = $response->json() ?? [];
        $success = (int) ($data['success'] ?? 0) === 1 && ! empty($data['redirect_url']);

        $payment->update([
            'status' => $success ? 'processing' : 'failed',
            'raw_response' => json_encode($data),
        ]);

        return [
            'success' => $success,
            'redirect_url' => $data['redirect_url'] ?? null,
            'message' => $data['message'] ?? null,
            'payment' => $payment,
        ];
    }

    /**
     * Vérifie qu'une notification IPN provient réellement de PayTech, par HMAC-SHA256 —
     * méthode documentée sur doc.paytech.sn : Message = "{final_item_price}|{ref_command}|
     * {api_key}", signé avec la clé secrète. La méthode alternative documentée (comparaison
     * des empreintes SHA-256 des identifiants) n'est délibérément pas utilisée : ces empreintes
     * sont constantes sur toutes les notifications du compte, donc rejouables — elles ne
     * prouvent pas l'authenticité du contenu (montant, référence) de CETTE notification.
     */
    public function verifyIpn(array $payload): bool
    {
        $secret = config('services.paytech.secret');
        $key = config('services.paytech.key');

        if (! $secret || ! $key) {
            return false;
        }

        if (empty($payload['hmac_compute']) || ! is_string($payload['hmac_compute'])) {
            return false;
        }

        if (empty($payload['ref_command']) || ! is_string($payload['ref_command'])) {
            return false;
        }

        $amount = $payload['final_item_price'] ?? null;
        if ($amount === null) {
            return false;
        }

        $message = $amount.'|'.$payload['ref_command'].'|'.$key;
        $expected = hash_hmac('sha256', $message, $secret);

        return hash_equals($expected, $payload['hmac_compute']);
    }

    /**
     * Réduit un montant du taux de frais que PayTech ajoute côté client (services.paytech.
     * fee_rate) — c'est ce montant réduit qui est envoyé comme item_price, pas le total réel de
     * la commande. Retourne le montant inchangé si fee_rate vaut 0 (désactivé).
     */
    private function applyFeeCompensation(int $amount): int
    {
        $rate = (float) config('services.paytech.fee_rate', 0);

        if ($rate <= 0) {
            return $amount;
        }

        return (int) round($amount / (1 + $rate));
    }

    /**
     * Montant minimum à exiger dans la notification IPN pour un total de commande donné — le
     * montant réellement envoyé à PayTech (voir applyFeeCompensation), jamais le total complet
     * de la commande quand la compensation de frais est active, sous peine de rejeter à tort des
     * paiements pourtant légitimes.
     */
    public function minimumAcceptableAmount(int $orderTotal): int
    {
        return $this->applyFeeCompensation($orderTotal);
    }

    /**
     * Référence unique à CETTE tentative de paiement — le numéro de commande seul ne suffit
     * pas dès qu'une commande peut être payée en plusieurs essais (annulation, échec, retry).
     */
    private function generateRefCommand(Order $order): string
    {
        $attempt = $order->payments()->count() + 1;

        return $order->order_number.'-'.str_pad((string) $attempt, 2, '0', STR_PAD_LEFT);
    }

    /**
     * URL fixe configurée (PAYTECH_SUCCESS_URL/CANCEL_URL), sinon URL générée pour cette
     * commande précise via route(). La commande est identifiable soit par le paramètre de
     * route ({order}), soit, avec une URL fixe, par le paramètre ?ref= ajouté ici.
     */
    private function returnUrl(string $configKey, string $routeName, Order $order): string
    {
        $configured = config('services.paytech.'.$configKey);

        if ($configured) {
            $separator = str_contains($configured, '?') ? '&' : '?';

            return $configured.$separator.'ref='.$order->order_number;
        }

        return route($routeName, $order);
    }
}
