<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->text('diagnostico')->nullable();
            $table->text('tratamiento_indicaciones')->nullable();
            $table->text('recomendaciones')->nullable();
            $table->boolean('requiere_seguimiento')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'diagnostico',
                'tratamiento_indicaciones',
                'recomendaciones',
                'requiere_seguimiento',
            ]);
        });
    }
};
