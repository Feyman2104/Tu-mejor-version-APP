<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos para el motor de rutinas científico (P1):
 * - activity_level: nivel de actividad diaria (reemplaza funcionalmente a 'mobility',
 *   que se conserva por retrocompatibilidad).
 * - split_type: tipo de organización elegido ('auto' = que el sistema recomiende).
 * - has_trained_before / last_trained: historial para decidir fase de adaptación/readaptación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('activity_level', ['sedentary', 'lightly_active', 'active', 'very_active'])
                ->nullable()->after('mobility');
            $table->enum('split_type', ['auto', 'full_body', 'upper_lower', 'ppl', 'weider'])
                ->default('auto')->after('preferred_muscles');
            $table->boolean('has_trained_before')->nullable()->after('split_type');
            $table->enum('last_trained', ['never', 'currently', 'lt_1m', '1_3m', '3_6m', 'gt_6m'])
                ->nullable()->after('has_trained_before');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['activity_level', 'split_type', 'has_trained_before', 'last_trained']);
        });
    }
};
