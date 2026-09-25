<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "en_attente_paiement" — statut initial d'une commande réglée en ligne (Wave/Orange Money/
     * Carte), tant que le paiement n'est pas confirmé par le serveur (webhook/IPN vérifié).
     * Distinct de "recue" (qui reste le statut initial du paiement à la livraison, jamais
     * bloquant) : une commande ne doit jamais apparaître comme "reçue"/prête à traiter alors
     * qu'elle n'a en réalité jamais été payée.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE orders DROP CONSTRAINT orders_status_check');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status IN ('en_attente_paiement', 'recue', 'confirmee', 'en_preparation', 'expediee', 'livree', 'annulee'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE orders SET status = 'annulee' WHERE status = 'en_attente_paiement'");
        DB::statement('ALTER TABLE orders DROP CONSTRAINT orders_status_check');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status IN ('recue', 'confirmee', 'en_preparation', 'expediee', 'livree', 'annulee'))");
    }
};
