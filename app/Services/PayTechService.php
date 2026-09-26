<?php

namespace App\Services;

use App\Models\PaymentAttempt;
use Illuminate\Support\Facades\Http;

/**
 * Paiement en ligne Wave/Orange Money/Carte bancaire via l'agrégateur PayTech (paytech.sn),
 * seule intégration de paiement en ligne de l'application pour ces 3 moyens — voir doc.paytech.sn
 * pour la référence de chaque paramètre/endpoint utilisé ici.
 *
 * Opère sur une PaymentAttempt (tentative), jamais sur une Order : la commande n'est créée
 * qu'après confirmation réelle du paiement (voir OrderService::createFromAttempt, appelé depuis
 * PayTechController::ipn) — pas avant, contrairement à Djamo/Free Money (PayDunyaService).
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
     * Démarre une tentative de paiement : appelle PayTech et renvoie l'URL de redirection vers sa
     * page de paiement hébergée. Le statut/la réponse brute sont stockés directement sur la
     * tentative (pas de ligne `payments` avant succès, puisqu'aucune commande n'existe encore).
     *
     * N'échoue jamais par exception (erreur réseau/API PayTech) — renvoie un tableau
     * ['success' => false, 'message' => ...] pour laisser l'appelant proposer une alternative
     * (ex. le paiement à la livraison).
     */
    public function createPayment(PaymentAttempt $attempt): array
    {
        $key = config('services.paytech.key');
        $secret = config('services.paytech.secret');

        if (! $key || ! $secret) {
            return ['success' => false, 'redirect_url' => null, 'message' => 'Paiement en ligne non configuré.', 'attempt' => $attempt];
        }

        // amount garde le vrai total de la tentative (comptabilité/rapports une fois la commande
        // matérialisée) — seul le montant envoyé à PayTech (item_price) est réduit, pour que les
        // frais qu'ils ajoutent côté client sur leur page de paiement retombent sur ce vrai total.
        // Voir le commentaire sur services.paytech.fee_rate.
        $sentAmount = $this->applyFeeCompensation((int) $attempt->amount);

        $attempt->update(['status' => 'pending']);

        try {
            $response = Http::withHeaders([
                'API_KEY' => $key,
                'API_SECRET' => $secret,
            ])->asJson()->post(self::REQUEST_PAYMENT_URL, [
                'item_name' => 'Commande Khalil Déco',
                'item_price' => $sentAmount,
                'ref_command' => $attempt->reference,
                'command_name' => 'Khalil Déco — '.$attempt->reference,
                'currency' => 'XOF',
                'env' => config('services.paytech.env', 'test'),
                'ipn_url' => config('services.paytech.ipn_url') ?: route('paytech.ipn'),
                'success_url' => $this->returnUrl('success_url', 'paytech.success', $attempt),
                'cancel_url' => $this->returnUrl('cancel_url', 'paytech.cancel', $attempt),
                'target_payment' => self::TARGET_PAYMENT_MAP[$attempt->payment_method] ?? null,
                'custom_field' => json_encode([
                    'attempt_id' => $attempt->id,
                    'user_id' => $attempt->user_id,
                ]),
            ]);
        } catch (\Throwable $e) {
            report($e);
            $attempt->update(['status' => 'failed', 'raw_response' => $e->getMessage()]);

            return ['success' => false, 'redirect_url' => null, 'message' => 'Service de paiement injoignable.', 'attempt' => $attempt];
        }

        $data = $response->json() ?? [];
        $success = (int) ($data['success'] ?? 0) === 1 && ! empty($data['redirect_url']);

        $attempt->update([
            'status' => $success ? 'processing' : 'failed',
            'raw_response' => json_encode($data),
        ]);

        return [
            'success' => $success,
            'redirect_url' => $data['redirect_url'] ?? null,
            'message' => $data['message'] ?? null,
            'attempt' => $attempt,
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
     * Montant minimum à exiger dans la notification IPN pour un montant de tentative donné — le
     * montant réellement envoyé à PayTech (voir applyFeeCompensation), jamais le total complet
     * quand la compensation de frais est active, sous peine de rejeter à tort des paiements
     * pourtant légitimes.
     */
    public function minimumAcceptableAmount(int $attemptAmount): int
    {
        return $this->applyFeeCompensation($attemptAmount);
    }

    /**
     * URL fixe configurée (PAYTECH_SUCCESS_URL/CANCEL_URL), sinon URL générée pour cette
     * tentative précise via route(). La tentative est identifiable soit par le paramètre de
     * route ({attempt}), soit, avec une URL fixe, par le paramètre ?ref= ajouté ici.
     */
    private function returnUrl(string $configKey, string $routeName, PaymentAttempt $attempt): string
    {
        $configured = config('services.paytech.'.$configKey);

        if ($configured) {
            $separator = str_contains($configured, '?') ? '&' : '?';

            return $configured.$separator.'ref='.$attempt->reference;
        }

        return route($routeName, $attempt);
    }
}
