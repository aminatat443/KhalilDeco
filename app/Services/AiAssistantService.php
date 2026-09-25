<?php

namespace App\Services;

use App\Models\Delivery;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiAssistantService
{
    /**
     * Assistant FAQ boutique (livraison, retours, paiement, tailles...) — pas de recherche
     * produit en temps réel, réponses cadrées au contenu de Khalil Déco uniquement.
     *
     * Utilise l'API Google Gemini (gratuite via aistudio.google.com, sans carte bancaire).
     *
     * @param  array<int, array{role: string, content: string}>  $messages  historique de la conversation, du plus ancien au plus récent
     */
    public function reply(array $messages): string
    {
        $key = config('services.gemini.key');

        if (! $key) {
            throw new RuntimeException("Clé API Gemini manquante (GEMINI_API_KEY dans .env).");
        }

        $model = config('services.gemini.model');

        // Les coupures réseau transitoires vers l'API Gemini ne sont pas rares — une
        // nouvelle tentative rapide absorbe la plupart de ces échecs ponctuels.
        $response = Http::timeout(25)->retry(2, 300, throw: false)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}",
            [
                'system_instruction' => [
                    'parts' => [['text' => $this->systemPrompt()]],
                ],
                'contents' => array_map(fn (array $message) => [
                    'role' => $message['role'] === 'assistant' ? 'model' : 'user',
                    'parts' => [['text' => $message['content']]],
                ], $messages),
                'generationConfig' => [
                    'maxOutputTokens' => 1024,
                ],
            ]
        );

        if ($response->failed()) {
            throw new RuntimeException('Gemini API error: '.$response->body());
        }

        return $response->json('candidates.0.content.parts.0.text') ?? "Désolé, je n'ai pas pu répondre. Réessayez.";
    }

    private function systemPrompt(): string
    {
        $zones = Delivery::where('is_active', true)->get()
            ->map(fn (Delivery $d) => $d->fee > 0
                ? "- {$d->zone} : {$d->fee} FCFA, {$d->estimated_days}"
                : "- {$d->zone} : {$d->estimated_days} (contacter la boutique sur WhatsApp)")
            ->implode("\n");

        return <<<PROMPT
            Tu es l'assistant virtuel de Khalil Déco, une boutique en ligne sénégalaise de mode et lifestyle
            (vêtements, chaussures, accessoires, décoration maison). Tu réponds en français, de façon
            courte, chaleureuse et professionnelle.

            Informations fiables sur la boutique :
            - Univers proposés : Femme, Homme, Chaussures, Accessoires, Maison & Décoration.
            - Livraison partout au Sénégal :
            {$zones}
            - Paiement : à la livraison (espèces). Les paiements en ligne (Wave, Orange Money, carte) arrivent prochainement.
            - Retours : possibles dans les 7 jours après réception, depuis la page "Mes commandes" du compte client. Remboursement par défaut en crédit boutique.
            - Compte client : création par email/mot de passe ou connexion Google, accessible depuis l'icône en haut du site.
            - La boutique ne vend qu'en FCFA, en français uniquement.

            Règles :
            - Réponds uniquement aux questions liées à Khalil Déco (livraison, retours, paiement, compte, tailles, utilisation du site).
            - Si tu ne connais pas une information précise (stock d'un produit précis, délai exact d'une commande en cours), invite poliment le client à consulter la page du produit ou "Mes commandes", ou à contacter le service client.
            - Ne réponds pas aux questions sans rapport avec la boutique ; redirige poliment vers le sujet.
            - Reste bref : 2 à 4 phrases maximum, sans listes à puces sauf si vraiment utile.
            PROMPT;
    }
}
