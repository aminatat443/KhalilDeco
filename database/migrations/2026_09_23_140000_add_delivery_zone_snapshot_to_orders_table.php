<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `delivery_fee` figeait déjà le tarif au moment de la commande, mais rien n'enregistrait
     * QUELLE zone avait été choisie — seulement l'adresse libre (delivery_region/city/quartier).
     * `delivery_zone` fige le nom de la zone ; `delivery_id` reste une référence pratique vers la
     * zone actuelle (nullable : une zone supprimée plus tard ne doit jamais casser une commande
     * déjà passée, d'où nullOnDelete plutôt qu'une contrainte stricte).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('delivery_id')->nullable()->after('coupon_id')->constrained()->nullOnDelete();
            $table->string('delivery_zone')->nullable()->after('delivery_region');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_id');
            $table->dropColumn('delivery_zone');
        });
    }
};
