<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade el campo 'sex' al perfil del usuario.
 * Necesario para calcular el BMR con la fórmula de Mifflin-St Jeor correcta:
 *   Hombre: +5  al final
 *   Mujer:  -161 al final
 * y para adaptar las rutinas a las diferencias fisiológicas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('sex', ['male', 'female', 'other'])
                ->nullable()
                ->after('age');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sex');
        });
    }
};
