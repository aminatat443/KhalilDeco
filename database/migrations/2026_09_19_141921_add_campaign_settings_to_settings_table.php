<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('abandoned_cart_enabled')->default(true)->after('rccm');
            $table->unsignedInteger('abandoned_cart_delay_days')->default(2)->after('abandoned_cart_enabled');
            $table->boolean('low_stock_favorite_enabled')->default(true)->after('abandoned_cart_delay_days');
            $table->unsignedInteger('low_stock_threshold')->default(5)->after('low_stock_favorite_enabled');
            $table->boolean('newsletter_enabled')->default(true)->after('low_stock_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'abandoned_cart_enabled', 'abandoned_cart_delay_days',
                'low_stock_favorite_enabled', 'low_stock_threshold', 'newsletter_enabled',
            ]);
        });
    }
};
