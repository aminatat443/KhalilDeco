<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alerte automatique "produit favori en promotion" — même mécanique que le stock faible
     * (low_stock_notified_at) : évite de notifier deux fois le même client pour la même
     * promotion, tout en permettant une nouvelle alerte si le produit repasse en promotion plus tard.
     */
    public function up(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->timestamp('promo_notified_at')->nullable()->after('low_stock_notified_at');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('promotion_favorite_enabled')->default(true)->after('low_stock_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->dropColumn('promo_notified_at');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('promotion_favorite_enabled');
        });
    }
};
