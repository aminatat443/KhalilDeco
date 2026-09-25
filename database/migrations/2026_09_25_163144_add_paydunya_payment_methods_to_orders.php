<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "djamo" et "free_money" — nouveaux moyens de paiement en ligne, tous deux traités via
     * PayDunya (voir PayDunyaService), en plus de wave/orange_money/carte déjà en place via
     * PayTech et cod/especes déjà existants — aucun de ces derniers n'est modifié.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE orders DROP CONSTRAINT orders_payment_method_check');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_method_check CHECK (payment_method IN ('wave', 'orange_money', 'carte', 'djamo', 'free_money', 'cod', 'especes'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE orders DROP CONSTRAINT orders_payment_method_check');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_method_check CHECK (payment_method IN ('wave', 'orange_money', 'carte', 'cod', 'especes'))");
    }
};
