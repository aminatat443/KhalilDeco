<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historique horodaté des événements commande/paiement (créée, paiement initié, réussi,
     * échoué, annulé, expiré...) — `payments.raw_response` ne garde que le dernier IPN reçu,
     * pas les transitions intermédiaires. `event` reste une chaîne libre plutôt qu'une contrainte
     * CHECK : la liste des types d'événements est amenée à grandir sans nécessiter de migration.
     */
    public function up(): void
    {
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->string('reference')->nullable();
            $table->string('actor_type')->default('system'); // system, user
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
