<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "processing" (en cours : le client a été redirigé vers la passerelle, en attente de
     * l'IPN) et "cancelled" (le client a annulé sur la page de paiement) s'ajoutent à
     * pending/success/failed/refunded — nécessaires pour distinguer ces cas dans l'admin
     * (section 11 de l'intégration PayTech). `gateway` identifie le prestataire qui a traité
     * le paiement (ex. "paytech") — distinct de `provider`, qui reste le moyen de paiement
     * (wave/orange_money/carte/especes/cod) ; null pour les paiements enregistrés manuellement.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE payments DROP CONSTRAINT payments_status_check');
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK (status IN ('pending', 'processing', 'success', 'failed', 'cancelled', 'refunded'))");

        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway')->nullable()->after('provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('gateway');
        });

        DB::statement("UPDATE payments SET status = 'failed' WHERE status IN ('processing', 'cancelled')");
        DB::statement('ALTER TABLE payments DROP CONSTRAINT payments_status_check');
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK (status IN ('pending', 'success', 'failed', 'refunded'))");
    }
};
