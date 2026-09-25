<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 4e type de campagne : promotion — notifie les clients ayant mis en favori un produit qui
     * vient de passer en promotion (déclenché manuellement depuis la fiche promotion, voir
     * PromotionController::sendEmail()).
     */
    public function up(): void
    {
        if (DB::table('email_templates')->where('key', 'promotion')->exists()) {
            return;
        }

        DB::table('email_templates')->insert([
            'key' => 'promotion',
            'subject' => 'Une promotion sur vos favoris chez Khalil Déco',
            'title' => 'Profitez-en avant la fin de la promotion !',
            'content' => "Bonjour [Prénom],\n\nBonne nouvelle : un produit que vous avez ajouté à vos favoris est actuellement en promotion.\n\n[Produit]\n\nProfitez-en dès maintenant avant la fin de l'offre.\n\n[NomBoutique]",
            'button_text' => 'Voir le produit',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('email_templates')->where('key', 'promotion')->delete();
    }
};
