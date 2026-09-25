<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

/**
 * Paiement en ligne Wave — intégration directe (Checkout Sessions), sans passer par un
 * agrégateur. Référence : docs.wave.com (Checkout API + Webhooks). Wave répond dans un délai
 * garanti sous 30 minutes (`when_expires`) ; passé ce délai la session expire côté Wave.
 */
class WaveService
{
    private const BASE_URL = 'https://api.wave.com/v1';

    /**
     * Démarre une session de paiement Wave et renvoie l'URL d'ouverture de l'app Wave
     * (`wave_launch_url`). N'échoue jamais par exception — renvoie
     * ['success' => false, 'message' => ...] en cas d'erreur réseau/API.
     */
    public function createPayment(Order $order): array
    {
        $key = config('services.wave.key');

        if (! $key) {
            return ['success' => false, 'redirect_url' => null, 'message' => 'Paiement Wave non configuré.', 'payment' => null];
        }

        $refCommand = $this->generateRefCommand($order);

        $payment = $order->payments()->create([
            'provider' => 'wave',
            'gateway' => 'wave',
            'transaction_id' => $refCommand,
            'amount' => (int) $order->total,
            'status' => 'pending',
        ]);

        try {
            $response = Http::withToken($key)->post(self::BASE_URL.'/checkout/sessions', [
                // Wave attend l'amount en chaîne de caractères (voir docs.wave.com/checkout).
                'amount' => (string) (int) $order->total,
                'currency' => 'XOF',
                'client_reference' => $refCommand,
                'error_url' => config('services.wave.error_url') ?: route('wave.cancel', $order),
                'success_url' => config('services.wave.success_url') ?: route('wave.success', $order),
            ]);
        } catch (\Throwable $e) {
            report($e);
            $payment->update(['status' => 'failed', 'raw_response' => $e->getMessage()]);

            return ['success' => false, 'redirect_url' => null, 'message' => 'Service Wave injoignable.', 'payment' => $payment];
        }

        $data = $response->json() ?? [];
        $success = $response->successful() && ! empty($data['wave_launch_url']);

        $payment->update([
            'status' => $success ? 'processing' : 'failed',
            'transaction_id' => $data['id'] ?? $refCommand,
            'raw_response' => json_encode($data),
        ]);

        return [
            'success' => $success,
            'redirect_url' => $data['wave_launch_url'] ?? null,
            'message' => $data['message'] ?? ($data['code'] ?? null),
            'payment' => $payment,
        ];
    }

    /**
     * Vérifie l'en-tête `Wave-Signature` d'une notification webhook — format documenté
     * "t=<timestamp>,v1=<signature>[,v1=<signature2>...]" (rotation de clé possible, donc
     * plusieurs v1 potentielles). Message signé = timestamp concaténé au corps BRUT de la
     * requête (jamais le JSON re-sérialisé, qui ne correspondrait plus à la signature).
     * Rejette toute notification vieille de plus de 5 minutes (rejeu).
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader): bool
    {
        $secret = config('services.wave.webhook_secret');

        if (! $secret || ! $signatureHeader) {
            return false;
        }

        $parts = explode(',', $signatureHeader);
        $timestamp = null;
        $signatures = [];

        foreach ($parts as $part) {
            [$k, $v] = array_pad(explode('=', $part, 2), 2, null);
            if ($k === 't') {
                $timestamp = $v;
            } elseif ($k === 'v1' && $v) {
                $signatures[] = $v;
            }
        }

        if (! $timestamp || empty($signatures)) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.$rawBody, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    private function generateRefCommand(Order $order): string
    {
        $attempt = $order->payments()->count() + 1;

        return $order->order_number.'-'.str_pad((string) $attempt, 2, '0', STR_PAD_LEFT);
    }
}
