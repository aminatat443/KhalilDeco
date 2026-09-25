<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Nullable et volontairement au niveau produit (pas variante) : sert au calcul de la
            // marge/valeur du stock dans les tableaux de bord, uniquement quand renseigné — jamais
            // une valeur estimée à sa place (section 29 du cahier des charges multi-rôles).
            $table->unsignedInteger('cost_price')->nullable()->after('old_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
