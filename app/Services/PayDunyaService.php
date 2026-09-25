<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

/**
 * Paiement en ligne Djamo (SN) et Free Money — intégration directe PayDunya (developers.
 * paydunya.com), sans passer par PayTech/Wave/Orange Money déjà en place pour les autres moyens.
 * Endpoints, en-têtes et format de requête/réponse vérifiés sur le SDK PHP officiel
 * (github.com/paydunyadev/paydunya-php-composer), la documentation publique ne détaillant pas
 * tout dans le corps de ses pages.
 */
class PayDunyaService
{
    private const BASE_URL = 'https://app.paydunya.com';

    /**
     * Slug de canal PayDunya par moyen de paiement — restreint la page de paiement hébergée à
     * ce seul canal, plutôt que de proposer tous les opérateurs qu'ils supportent.
     */
    private const CHANNEL_MAP = [
        'wave' => 'wave-senegal',
        'orange_money' => 'orange-money-senegal',
        'carte' => 'card',
        'djamo' => 'djamo-sn',
        'free_money' => 'free-money-senegal',
    ];

    /**
     * Démarre une tentative de paiement et renvoie l'URL de la page de paiement hébergée
     * PayDunya. N'échoue jamais par exception (erreur réseau/API) — renvoie
     * ['success' => false, 'message' => ...] pour laisser l'appelant proposer une alternative.
     */
    public function createPayment(Order $order): array
    {
        $keys = $this->keys();

        if (! $keys) {
            return ['success' => false, 'redirect_url' => null, 'message' => 'Paiement en ligne non configuré.', 'payment' => null];
        }

        $refCommand = $this->generateRefCommand($order);

        $payment = $order->payments()->create([
            'provider' => $order->payment_method,
            'gateway' => 'paydunya',
            'transaction_id' => $refCommand,
            'amount' => (int) $order->total,
            'status' => 'pending',
        ]);

        $payload = [
            'invoice' => [
                'total_amount' => (int) $order->total,
                'description' => 'Commande '.$order->order_number.' — Khalil Déco',
                'items' => [
                    'item_0' => [
                        'name' => 'Commande '.$order->order_number,
                        'quantity' => 1,
                        'unit_price' => (int) $order->total,
                        'total_price' => (int) $order->total,
                        'description' => '',
                    ],
                ],
                'taxes' => [],
                'channels' => array_filter([self::CHANNEL_MAP[$order->payment_method] ?? null]),
            ],
            'store' => [
                'name' => config('services.paydunya.store_name'),
                'tagline' => 'Décoration & Quincaillerie',
                'postal_address' => 'Dakar, Sénégal',
                'phone' => '',
                'logo_url' => asset('images/logo_khalil_deco_icon.png'),
                'website_url' => config('services.paydunya.store_website_url'),
            ],
            'custom_data' => [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'user_id' => $order->user_id,
                'ref_command' => $refCommand,
            ],
            'actions' => [
                'cancel_url' => $this->returnUrl('cancel_url', 'paydunya.cancel', $order),
                'return_url' => $this->returnUrl('success_url', 'paydunya.success', $order),
                'callback_url' => config('services.paydunya.callback_url') ?: route('paydunya.callback'),
            ],
        ];

        try {
            $response = Http::withHeaders($this->headers())
                ->post(self::BASE_URL.$this->invoicePath('create'), $payload);
        } catch (\Throwable $e) {
            report($e);
            $payment->update(['status' => 'failed', 'raw_response' => $e->getMessage()]);

            return ['success' => false, 'redirect_url' => null, 'message' => 'Service PayDunya injoignable.', 'payment' => $payment];
        }

        $data = $response->json() ?? [];
        $success = (string) ($data['response_code'] ?? '') === '00' && ! empty($data['token']);

        $payment->update([
            'status' => $success ? 'processing' : 'failed',
            'raw_response' => json_encode($data),
        ]);

        return [
            'success' => $success,
            // response_text est directement l'URL de la page de paiement en cas de succès
            // (voir Checkout::create() du SDK officiel) — jamais un message dans ce cas.
            'redirect_url' => $success ? ($data['response_text'] ?? null) : null,
            'message' => $success ? null : ($data['response_text'] ?? null),
            'payment' => $payment,
        ];
    }

    /**
     * Interroge directement PayDunya pour connaître le statut RÉEL d'une facture, plutôt que de
     * faire confiance au contenu de la notification callback — c'est le mécanisme que leur
     * propre SDK officiel utilise (Checkout::confirm()), pas une précaution ajoutée ici : la
     * documentation publique ne décrit d'ailleurs aucune signature pour la notification elle-même.
     *
     * @return array{status: string, total_amount: int}|null null si injoignable/jeton inconnu
     */
    public function confirmStatus(string $token): ?array
    {
        $keys = $this->keys();

        if (! $keys) {
            return null;
        }

        try {
            $response = Http::withHeaders($this->headers())
                ->get(self::BASE_URL.$this->invoicePath('confirm').$token);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json() ?? [];

        if (empty($data['status'])) {
            return null;
        }

        return [
            'status' => $data['status'],
            'total_amount' => (int) ($data['invoice']['total_amount'] ?? 0),
            'raw' => $data,
        ];
    }

    private function keys(): ?array
    {
        $masterKey = config('services.paydunya.master_key');
        $privateKey = config('services.paydunya.private_key');
        $publicKey = config('services.paydunya.public_key');
        $token = config('services.paydunya.token');

        if (! $masterKey || ! $privateKey || ! $publicKey || ! $token) {
            return null;
        }

        return compact('masterKey', 'privateKey', 'publicKey', 'token');
    }

    private function headers(): array
    {
        $keys = $this->keys();

        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'PAYDUNYA-MASTER-KEY' => $keys['masterKey'],
            'PAYDUNYA-PRIVATE-KEY' => $keys['privateKey'],
            'PAYDUNYA-PUBLIC-KEY' => $keys['publicKey'],
            'PAYDUNYA-TOKEN' => $keys['token'],
            'PAYDUNYA-MODE' => config('services.paydunya.mode', 'test'),
        ];
    }

    /**
     * "sandbox-api" en mode test, "api" en mode live — jamais un paramètre de requête, PayDunya
     * détermine l'environnement uniquement par l'URL appelée (vérifié sur leur SDK officiel).
     */
    private function invoicePath(string $action): string
    {
        $base = config('services.paydunya.mode') === 'live' ? '/api/v1' : '/sandbox-api/v1';

        return $action === 'create'
            ? $base.'/checkout-invoice/create'
            : $base.'/checkout-invoice/confirm/';
    }

    private function generateRefCommand(Order $order): string
    {
        $attempt = $order->payments()->count() + 1;

        return $order->order_number.'-'.str_pad((string) $attempt, 2, '0', STR_PAD_LEFT);
    }

    private function returnUrl(string $configKey, string $routeName, Order $order): string
    {
        $configured = config('services.paydunya.'.$configKey);

        if ($configured) {
            $separator = str_contains($configured, '?') ? '&' : '?';

            return $configured.$separator.'ref='.$order->order_number;
        }

        return route($routeName, $order);
    }
}
