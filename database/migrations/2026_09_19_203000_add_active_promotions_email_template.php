<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 6e type de campagne : promotions (catalogue) — diffusion aux abonnés newsletter de TOUS les
     * produits actuellement marqués "en promotion" (Product.is_promo), détectés automatiquement
     * (voir CampaignController::sendActivePromotions()). Distinct du modèle "promotion" qui, lui,
     * ne cible que les clients ayant mis en favori le produit concerné.
     */
    public function up(): void
    {
        if (DB::table('email_templates')->where('key', 'active_promotions')->exists()) {
            return;
        }

        DB::table('email_templates')->insert([
            'key' => 'active_promotions',
            'subject' => 'Nos promotions du moment chez Khalil Déco',
            'title' => 'Profitez de nos promotions',
            'content' => "Bonjour [Prénom],\n\nDécouvrez les produits actuellement en promotion chez [NomBoutique]. Des prix réduits, pour un temps limité seulement !",
            'button_text' => 'Voir les promotions',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('email_templates')->where('key', 'active_promotions')->delete();
    }
};
