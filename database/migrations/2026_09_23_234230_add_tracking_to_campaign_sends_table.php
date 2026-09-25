<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_sends', function (Blueprint $table) {
            $table->timestamp('opened_at')->nullable()->after('sent_at');
            $table->timestamp('clicked_at')->nullable()->after('opened_at');
            $table->unsignedInteger('click_count')->default(0)->after('clicked_at');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_sends', function (Blueprint $table) {
            $table->dropColumn(['opened_at', 'clicked_at', 'click_count']);
        });
    }
};
