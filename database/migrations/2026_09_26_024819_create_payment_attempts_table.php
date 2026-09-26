<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tentative de paiement Wave/Orange Money/Carte AVANT qu'une commande existe — la commande
     * n'est créée (voir OrderService::createFromAttempt) qu'une fois le paiement réellement
     * confirmé par PayTech. cart_snapshot/delivery_snapshot figent tout ce qu'il faut pour
     * matérialiser la commande depuis l'IPN, qui arrive côté serveur sans panier de session ni
     * utilisateur connecté disponibles.
     */
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_method');
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedInteger('amount');
            $table->json('cart_snapshot');
            $table->json('delivery_snapshot');
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('raw_response')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
