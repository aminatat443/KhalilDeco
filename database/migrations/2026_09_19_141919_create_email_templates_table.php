<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contenu éditable des 3 types d'emails automatiques/newsletter — un enregistrement par
     * clé (`abandoned_cart`, `low_stock_favorite`, `newsletter`), pré-rempli par le seeder de
     * cette migration avec le message par défaut du cahier des charges. Les variables
     * ([Prénom], [Produit], [LienPanier]…) sont substituées à l'envoi (voir EmailTemplate::render()).
     */
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('subject');
            $table->string('title')->nullable();
            $table->text('content');
            $table->string('button_text')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('email_templates')->insert([
            [
                'key' => 'abandoned_cart',
                'subject' => 'Votre panier vous attend chez Khalil Déco',
                'title' => 'Vous avez oublié quelque chose ?',
                'content' => "Bonjour [Prénom],\n\nVous avez laissé certains articles dans votre panier. Ils sont toujours disponibles pour le moment. Retrouvez votre sélection et finalisez votre commande directement depuis votre panier.\n\nÀ bientôt,\n[NomBoutique]",
                'button_text' => 'Reprendre ma commande',
                'image_url' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'low_stock_favorite',
                'subject' => 'Un produit de votre liste de favoris est bientôt épuisé',
                'title' => 'Vos favoris partent vite !',
                'content' => "Bonjour [Prénom],\n\nL'un des produits que vous avez ajouté à vos favoris est bientôt en rupture de stock.\n\n[Produit]\nStock restant : [Stock]\n\nSi vous souhaitez l'acheter, nous vous invitons à passer commande rapidement.\n\n[NomBoutique]",
                'button_text' => 'Voir mes favoris',
                'image_url' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'newsletter',
                'subject' => 'Les nouveautés Khalil Déco',
                'title' => null,
                'content' => '',
                'button_text' => 'En savoir plus',
                'image_url' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
