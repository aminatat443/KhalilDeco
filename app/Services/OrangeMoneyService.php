<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Paiement en ligne Orange Money — intégration directe (Web Payment API), sans agrégateur.
 * Référence : developer.orange.com/apis/om-webpay. La documentation publique de cette API ne
 * décrit AUCUN mécanisme de signature cryptographique pour la notification envoyée à notif_url
 * (contrairement à PayTech et Wave) — voir verifyAndFetchStatus() pour la façon dont ce projet
 * compense cette absence.
 */
class OrangeMoneyService
{
    private const BASE_URL = 'https://api.orange.com';

    /**
     * Jeton OAuth2 (client_credentials) mis en cache jusqu'à peu avant son expiration — évite
     * de redemander un jeton à chaque paiement, Orange les délivre pour plusieurs mois.
     */
    private function accessToken(): ?string
    {
        $clientId = config('services.orange_money.client_id');
        $clientSecret = config('services.orange_money.client_secret');

        if (! $clientId || ! $clientSecret) {
            return null;
        }

        return Cache::remember('orange_money_access_token', 3600, function () use ($clientId, $clientSecret) {
            $response = Http::asForm()
                ->withBasicAuth($clientId, $clientSecret)
                ->post(self::BASE_URL.'/oauth/v3/token', ['grant_type' => 'client_credentials']);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json() ?? [];

            // Redemande un jeton un peu avant son expiration réelle plutôt que pile dessus.
            $ttl = max(60, (int) ($data['expires_in'] ?? 3600) - 60);
            Cache::put('orange_money_access_token', $data['access_token'] ?? null, $ttl);

            return $data['access_token'] ?? null;
        });
    }

    /**
     * Démarre un paiement Orange Money et renvoie l'URL de paiement hébergée
     * (`payment_url`). N'échoue jamais par exception — renvoie
     * ['success' => false, 'message' => ...] en cas d'erreur réseau/API/configuration.
     */
    public function createPayment(Order $order): array
    {
        $merchantKey = config('services.orange_money.merchant_key');
        $token = $this->accessToken();

        if (! $merchantKey || ! $token) {
            return ['success' => false, 'redirect_url' => null, 'message' => 'Paiement Orange Money non configuré.', 'payment' => null];
        }

        $orderId = $this->generateRefCommand($order);
        $country = config('services.orange_money.country', 'sn');

        $payment = $order->payments()->create([
            'provider' => 'orange_money',
            'gateway' => 'orange_money',
            'transaction_id' => $orderId,
            'amount' => (int) $order->total,
            'status' => 'pending',
        ]);

        try {
            $response = Http::withToken($token)->post(
                self::BASE_URL."/orange-money-webpay/{$country}/v1/webpayment",
                [
                    'merchant_key' => $merchantKey,
                    'currency' => 'XOF',
                    'order_id' => $orderId,
                    'amount' => (int) $order->total,
                    'return_url' => config('services.orange_money.success_url') ?: route('orange-money.success', $order),
                    'cancel_url' => config('services.orange_money.cancel_url') ?: route('orange-money.cancel', $order),
                    'notif_url' => config('services.orange_money.notif_url') ?: route('orange-money.notif'),
                    'lang' => 'fr',
                ]
            );
        } catch (\Throwable $e) {
            report($e);
            $payment->update(['status' => 'failed', 'raw_response' => $e->getMessage()]);

            return ['success' => false, 'redirect_url' => null, 'message' => 'Service Orange Money injoignable.', 'payment' => $payment];
        }

        $data = $response->json() ?? [];
        $success = $response->successful() && ! empty($data['payment_url']) && ! empty($data['pay_token']);

        // pay_token et notif_token sont indispensables à la vérification ultérieure de la
        // notification (voir verifyAndFetchStatus) — conservés dans raw_response, pas de colonne
        // dédiée pour une paire de valeurs seulement utile en interne à ce service.
        $payment->update([
            'status' => $success ? 'processing' : 'failed',
            'raw_response' => json_encode($data),
        ]);

        return [
            'success' => $success,
            'redirect_url' => $data['payment_url'] ?? null,
            'message' => $data['message'] ?? null,
            'payment' => $payment,
        ];
    }

    /**
     * Interroge directement l'API Orange Money pour connaître le statut RÉEL d'une tentative,
     * plutôt que de faire confiance au contenu de la notification reçue sur notif_url.
     *
     * Pourquoi : la documentation publique d'Orange Money Web Payment ne décrit aucun mécanisme
     * de signature pour cette notification (à la différence de PayTech — HMAC-SHA256 — et de
     * Wave — en-tête Wave-Signature). Une notification y affirmant "paiement réussi" n'est donc
     * pas, à elle seule, une preuve : n'importe qui connaissant l'URL pourrait en poster une
     * fausse. Ce projet ne se fie donc JAMAIS au champ "status" de la notification — il ne s'en
     * sert que comme signal "va vérifier cette tentative", puis interroge lui-même l'API
     * transactionstatus, seule source faisant réellement autorité.
     *
     * @return string|null 'SUCCESS'|'FAILED'|'EXPIRED'|'PENDING'|'INITIATED'|null si injoignable
     */
    public function verifyAndFetchStatus(string $orderId, int $amount, string $payToken): ?string
    {
        $token = $this->accessToken();
        $country = config('services.orange_money.country', 'sn');

        if (! $token) {
            return null;
        }

        try {
            $response = Http::withToken($token)->post(
                self::BASE_URL."/orange-money-webpay/{$country}/v1/transactionstatus",
                ['order_id' => $orderId, 'amount' => $amount, 'pay_token' => $payToken]
            );
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return $response->json('status');
    }

    private function generateRefCommand(Order $order): string
    {
        $attempt = $order->payments()->count() + 1;

        return $order->order_number.'-'.str_pad((string) $attempt, 2, '0', STR_PAD_LEFT);
    }
}
