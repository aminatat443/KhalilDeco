<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Étend `campaigns` pour couvrir les 3 types du nouveau système (newsletter, panier
     * abandonné, favoris/stock faible) avec suivi de statut et de résultats — sans toucher aux
     * colonnes existantes (`type`, `product_ids`…), conservées pour ne rien casser côté
     * historique déjà en base.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('campaign_type')->default('newsletter')->after('type');
            $table->string('status')->default('pending')->after('campaign_type');
            $table->string('title')->nullable()->after('subject');
            $table->string('image_url')->nullable()->after('message');
            $table->string('button_text')->nullable()->after('image_url');
            $table->string('button_url')->nullable()->after('button_text');
            $table->unsignedInteger('sent_count')->default(0)->after('recipients_count');
            $table->unsignedInteger('failed_count')->default(0)->after('sent_count');
            $table->boolean('is_automatic')->default(false)->after('failed_count');
            $table->timestamp('sent_at')->nullable()->after('is_automatic');
        });

        // `product_ids` n'a de sens que pour l'ancien flux "campagne par produits" — le nouveau
        // constructeur newsletter ne le renseigne pas.
        Schema::table('campaigns', function (Blueprint $table) {
            $table->json('product_ids')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'campaign_type', 'status', 'title', 'image_url', 'button_text', 'button_url',
                'sent_count', 'failed_count', 'is_automatic', 'sent_at',
            ]);
        });
    }
};
