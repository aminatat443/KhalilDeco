<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Attributs génériques (Couleur, Puissance, Longueur...) — chaque catégorie choisit lesquels
     * s'appliquent à ses produits plutôt que d'avoir des colonnes figées (color/size/power/...)
     * qui ne conviennent qu'à la mode. Voir category_attribute pour l'association par catégorie.
     */
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            // 'select' : valeurs textuelles simples (ex. Puissance : 7W, 12W...)
            // 'color'  : valeurs avec un code couleur associé, affichées en pastilles
            $table->string('type')->default('select');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
