<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permet de programmer une campagne pour un envoi différé (section 9 du cahier des charges
     * "Nouvelle campagne") — traité par la commande `campaigns:send-scheduled`.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->timestamp('scheduled_at')->nullable()->after('sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('scheduled_at');
        });
    }
};
