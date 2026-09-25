<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Copie persistée du panier (le panier "vivant" reste en session) — uniquement pour les
     * clients connectés, seuls destinataires possibles d'un email de relance panier abandonné.
     * `updated_at` sert de date de dernière activité, `reminded_at` évite de relancer plusieurs
     * fois pour le même contenu de panier.
     */
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('items');
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
