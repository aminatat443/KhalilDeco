<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Combinaison d'attributs d'une variante (ex. Couleur=Blanc + Puissance=12W) — remplace,
     * pour les nouveaux produits, les colonnes figées color_id/size_id (conservées telles
     * quelles sur product_variants pour ne rien casser côté historique) par un système
     * générique où chaque catégorie choisit ses propres attributs (voir category_attribute).
     */
    public function up(): void
    {
        Schema::create('product_variant_attribute_value', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_variant_id', 'attribute_value_id'], 'variant_attr_value_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_attribute_value');
    }
};
