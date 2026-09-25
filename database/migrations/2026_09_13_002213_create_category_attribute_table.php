<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Association catégorie ↔ attributs disponibles (section admin "Attributs par catégorie") :
     * une catégorie "Éclairage" peut proposer Couleur/Puissance/Température, une catégorie
     * "Quincaillerie" plutôt Matière/Longueur — sans dupliquer les attributs par catégorie.
     */
    public function up(): void
    {
        Schema::create('category_attribute', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['category_id', 'attribute_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_attribute');
    }
};
