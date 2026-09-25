<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 5e type de campagne : nouveautés — diffusion aux abonnés newsletter de tous les produits
     * actuellement marqués "nouveau" (Product.is_new), détectés automatiquement (voir
     * CampaignController::sendNewArrivals()).
     */
    public function up(): void
    {
        if (DB::table('email_templates')->where('key', 'new_arrivals')->exists()) {
            return;
        }

        DB::table('email_templates')->insert([
            'key' => 'new_arrivals',
            'subject' => 'Découvrez les nouveautés Khalil Déco',
            'title' => 'Nos dernières nouveautés',
            'content' => "Bonjour [Prénom],\n\nDe nouveaux produits viennent d'arriver chez [NomBoutique]. Découvrez-les avant tout le monde !",
            'button_text' => 'Découvrir',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('email_templates')->where('key', 'new_arrivals')->delete();
    }
};
