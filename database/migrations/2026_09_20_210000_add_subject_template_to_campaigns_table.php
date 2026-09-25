<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Conserve l'objet "modèle" saisi par l'administrateur séparément de l'objet final
     * réellement envoyé (avec son suffixe de date unique par campagne) — `subject` reste
     * l'objet final déjà affiché partout dans l'historique, sans rien à changer côté vues.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('subject_template')->nullable()->after('subject');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('subject_template');
        });
    }
};
